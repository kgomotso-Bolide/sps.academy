<?php
declare(strict_types=1);

/* Shared start-up for every PHP entry point. Include this first, always.
 *
 * Targets PHP 8.0 syntax deliberately. The local machine runs 8.5 but Xneelo
 * shared hosting decides its own version, and finding out that a language
 * feature is unavailable after deploying is a bad way to find out. */

if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('This application requires PHP 8.0 or newer.');
}

define('APP_ROOT', dirname(__DIR__));
define('APP_BOOTED', true);

/* ---------------------------------------------------------------------------
   Configuration
   --------------------------------------------------------------------------- */

/**
 * Find and load the config file, searching from most explicit to most local.
 *
 * The candidate list is ordered so that a real server never accidentally picks
 * up a development config: ~/private/ is checked before the in-repo fallback,
 * and the in-repo fallback is gitignored so it cannot exist on a fresh deploy.
 */
function app_config(?string $key = null)
{
    static $cfg = null;

    if ($cfg === null) {
        $candidates = array_filter([
            getenv('SPS_CONFIG') ?: null,
            dirname(APP_ROOT) . '/private/sps-config.php',   // ~/private, web root is ~/<site>
            dirname(APP_ROOT, 2) . '/private/sps-config.php', // web root nested one deeper
            APP_ROOT . '/lib/config.local.php',              // development only, gitignored
        ]);

        $found = null;
        foreach ($candidates as $path) {
            if (is_file($path)) { $found = $path; break; }
        }

        if ($found === null) {
            app_fail('No configuration file found. Copy lib/config.sample.php to '
                   . '~/private/sps-config.php and fill it in.');
        }

        $cfg = require $found;
        if (!is_array($cfg)) {
            app_fail('The configuration file did not return an array.');
        }
        $cfg['_path'] = $found;
    }

    if ($key === null) return $cfg;

    // Dotted lookup: app_config('db.host')
    $node = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) return null;
        $node = $node[$part];
    }
    return $node;
}

/* ---------------------------------------------------------------------------
   Errors
   --------------------------------------------------------------------------- */

/**
 * Stop with a message the visitor can read and nothing they cannot.
 *
 * The detail goes to the log; the page gets a sentence. A database error must
 * never render the connection string, which is exactly what an uncaught PDO
 * exception does by default.
 */
function app_fail(string $detail, int $status = 500): void
{
    app_log('ERROR ' . $detail);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $detail . "\n");
        exit(1);
    }

    http_response_code($status);
    $debug = (bool) (app_config_safe('debug') ?? false);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title>'
       . '<div style="font:16px/1.6 system-ui,sans-serif;max-width:34rem;margin:18vh auto;padding:0 1.5rem">'
       . '<h1 style="font-size:1.3rem">Something went wrong at our end.</h1>'
       . '<p>Nothing you did caused this. Please try again in a moment — and if it '
       . 'keeps happening, email <a href="mailto:kgomotso@centenarynetworks.com">'
       . 'kgomotso@centenarynetworks.com</a> and say what you were doing.</p>'
       . ($debug ? '<pre style="white-space:pre-wrap;background:#f4f4f4;padding:1rem;'
                 . 'border-radius:6px;font-size:13px">' . htmlspecialchars($detail) . '</pre>' : '')
       . '</div>';
    exit;
}

/** app_config() itself can fail, so the error handler needs a version that cannot. */
function app_config_safe(string $key)
{
    try { return app_config($key); } catch (Throwable $e) { return null; }
}

function app_log(string $line): void
{
    $dir = dirname(APP_ROOT) . '/private/logs';
    if (!is_dir($dir)) {
        $dir = sys_get_temp_dir();
    }
    @file_put_contents(
        $dir . '/app-' . date('Y-m') . '.log',
        '[' . date('c') . '] ' . $line . "\n",
        FILE_APPEND | LOCK_EX
    );
}

set_exception_handler(function (Throwable $e): void {
    app_fail(get_class($e) . ': ' . $e->getMessage()
           . ' @ ' . $e->getFile() . ':' . $e->getLine());
});

/* ---------------------------------------------------------------------------
   Request helpers
   --------------------------------------------------------------------------- */

/**
 * A stable, non-reversible stand-in for the visitor's IP address.
 *
 * See the note on 'ip_pepper' in the config template: we keep the ability to
 * compare, and drop the ability to identify.
 */
function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    // Shared hosting sits behind a proxy; the left-most entry is the client.
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    }
    $pepper = (string) (app_config('ip_pepper') ?? '');
    if ($pepper === '') {
        // Better to refuse than to store something reversible by rainbow table.
        app_fail('ip_pepper is not set in the configuration file.');
    }
    return hash_hmac('sha256', $ip, $pepper);
}

/**
 * Start a session with cookie flags set BEFORE the cookie is issued.
 *
 * Called explicitly rather than on every request: an anonymous visitor reading
 * the course catalogue should not be given a cookie they never needed.
 */
function app_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $https = (($_SERVER['HTTPS'] ?? '') === 'on')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,      // not readable from JavaScript
        'samesite' => 'Lax',     // survives normal navigation, blocks cross-site POST
    ]);
    session_name('spsacad');
    session_start();
}

/**
 * Who is signed in, or null.
 *
 * Lives here rather than in lib/auth.php because lib/audit.php needs it, and
 * audit is loaded on pages that have nothing to do with signing in. It only
 * reads the session, so it costs nothing where nobody is signed in.
 */
function current_user_id(): ?int
{
    if (session_status() !== PHP_SESSION_ACTIVE) return null;
    return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Trimmed string from POST, capped so a hostile client cannot post a megabyte. */
function post_str(string $key, int $max = 500): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    $v = trim($v);
    return mb_substr($v, 0, $max);
}

function redirect(string $to): void
{
    header('Location: ' . $to, true, 303);
    exit;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
