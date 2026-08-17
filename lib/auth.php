<?php
declare(strict_types=1);

/* Signing in.
 *
 * Two properties this file is built around, both easy to lose by accident:
 *
 * 1. It must not tell an anonymous visitor whether an email address exists.
 *    "No such user" and "wrong password" are the same message, and an unknown
 *    address still gets a password check run against a dummy hash so that the
 *    two paths take a similar amount of time. Otherwise the login form becomes
 *    a way to enumerate everyone the company employs.
 *
 * 2. It must survive a password list being run at it. Attempts are counted per
 *    source and per address, from the audit table, and both have to be clear
 *    before a check is even attempted.
 *
 * There is no password-reset flow yet. Accounts are created by
 * tools/make-user.php from the command line, which is honest for the number of
 * people involved today and is noted as a gap rather than pretended away.
 */

defined('APP_BOOTED') or exit('lib/auth.php is not a page.');

const LOGIN_MAX_ATTEMPTS = 6;      // per source, and per address
const LOGIN_WINDOW_SECONDS = 900;  // 15 minutes

/**
 * A hash of a password nobody has, used to spend roughly the same time on an
 * unknown address as on a known one. Generated once at the cost of one hash.
 */
function auth_dummy_hash(): string
{
    static $h = null;
    return $h ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
}

function auth_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/** How many failed attempts match this column value inside the window. */
function auth_recent_failures(string $column, string $value): int
{
    $since = gmdate('Y-m-d H:i:s', time() - LOGIN_WINDOW_SECONDS);
    return (int) db_value(
        'SELECT COUNT(*) FROM audit_log
          WHERE tenant_id = ? AND action = ? AND ' . ($column === 'ip' ? 'ip_hash' : 'detail') . ' = ?
            AND created_at > ?',
        [tenant_id(), 'login.failed', $value, $since]
    );
}

function auth_locked_out(string $email): bool
{
    return auth_recent_failures('ip', client_ip_hash()) >= LOGIN_MAX_ATTEMPTS
        || auth_recent_failures('email', mb_strtolower($email)) >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Attempt a sign-in.
 *
 * @return array{0: bool, 1: string} [ok, message-for-the-visitor]
 */
function auth_attempt(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));

    /* The same sentence for every failure below. A visitor who mistyped their
       password and a stranger probing for valid addresses learn exactly the
       same thing from it, which is nothing. */
    $generic = 'That email address and password do not match. Please try again.';

    if ($email === '' || $password === '') {
        return [false, $generic];
    }

    if (auth_locked_out($email)) {
        audit('login.throttled', 'users', null, $email);
        return [false, 'Too many attempts. Please wait fifteen minutes and try again, '
                     . 'or email kgomotso@centenarynetworks.com if you are stuck.'];
    }

    $user = db_one(
        'SELECT * FROM users WHERE tenant_id = ? AND email = ?',
        [tenant_id(), $email]
    );

    // Runs either way — see the note on auth_dummy_hash().
    $hash = $user['password_hash'] ?? auth_dummy_hash();
    $ok   = password_verify($password, $hash);

    if (!$user || !$ok) {
        audit('login.failed', 'users', $user['id'] ?? null, $email);
        return [false, $generic];
    }

    if (($user['status'] ?? '') !== 'active') {
        audit('login.disabled', 'users', (int) $user['id'], $email);
        return [false, 'This account is not active. Please email '
                     . 'kgomotso@centenarynetworks.com.'];
    }

    /* If PHP's default algorithm has moved on since this hash was made, take the
       one moment we legitimately have the plain password and bring it forward. */
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        db_run('UPDATE users SET password_hash = ? WHERE id = ?',
               [auth_hash($password), (int) $user['id']]);
    }

    auth_sign_in($user);
    return [true, ''];
}

function auth_sign_in(array $user): void
{
    app_session_start();
    // A brand new session id, so a session fixed before sign-in is worthless.
    session_regenerate_id(true);

    $_SESSION['uid']  = (int) $user['id'];
    $_SESSION['role'] = (string) $user['role'];
    current_user(true);   // the cached "nobody" from a moment ago is now wrong

    db_run('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), (int) $user['id']]);
    audit('login.ok', 'users', (int) $user['id']);
}

function auth_sign_out(): void
{
    app_session_start();
    $uid = current_user_id();
    if ($uid !== null) audit('logout', 'users', $uid);

    current_user(true);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * The signed-in user, looked up once per request.
 *
 * $forget exists because the answer changes mid-request exactly twice — at
 * sign-in and at sign-out — and the cached "nobody is signed in" from before a
 * successful sign-in is worse than no cache at all. It sent the first
 * administrator who logged in to the public homepage instead of the
 * registrations list, because the role check ran against a stale null.
 */
function current_user(bool $forget = false): ?array
{
    static $user = null;
    static $looked = false;

    if ($forget) { $user = null; $looked = false; return null; }
    if ($looked) return $user;
    $looked = true;

    app_session_start();
    $id = current_user_id();
    if ($id === null) return null;

    $user = db_one('SELECT * FROM users WHERE id = ? AND tenant_id = ?', [$id, tenant_id()]);

    /* Belt and braces: a session can outlive the account it names. If the user
       was deleted, disabled, or belongs to another tenant, the session is not
       merely ignored — it is ended. */
    if (!$user || $user['status'] !== 'active') {
        auth_sign_out();
        return $user = null;
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

/** Send anyone who is not an administrator to the sign-in page. */
function require_admin(): array
{
    $u = current_user();
    if ($u === null) {
        redirect('login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin'));
    }
    if ($u['role'] !== 'admin') {
        audit('access.denied', 'page', null, (string) ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code(403);
        exit('You do not have access to this page.');
    }
    return $u;
}
