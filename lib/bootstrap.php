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

/* Errors are never shown to a visitor unless the config says debug, and the
 * config has not been read yet — so the safe setting goes on first and is
 * relaxed later, rather than the other way round. A fatal inside a database
 * call would otherwise print the connection string to the page.
 *
 * Set here rather than in .htaccess. php_flag only exists when PHP runs as an
 * Apache module; under FastCGI or FPM those directives are unknown and can make
 * the server refuse every .php request while static files serve perfectly. */
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

/* ---------------------------------------------------------------------------
   Configuration
   --------------------------------------------------------------------------- */

/** Normalise a path for comparison: real path where possible, forward slashes. */
function path_norm(string $p): string
{
    $r = realpath($p);
    return str_replace('\\', '/', $r === false ? $p : $r);
}

/**
 * Is $path inside $dir? Used to keep the configuration out of the web root.
 *
 * The trailing slash on $dir matters: without it, "/var/www/public_htmlX"
 * counts as inside "/var/www/public_html".
 */
function path_inside(string $path, string $dir): bool
{
    $path = path_norm($path);
    $dir  = rtrim(path_norm($dir), '/') . '/';
    return $dir !== '/' && str_starts_with($path, $dir);
}

/**
 * Find and load the config file.
 *
 * It walks UP from the application directory looking for private/sps-config.php,
 * and REFUSES any candidate that turns out to be inside the web root. That
 * refusal is the whole point of the function, and it is why this is not just a
 * hardcoded path.
 *
 * The two layouts this has to serve put the web root in different places:
 *
 *   subdomain   ~/sps.centenarynetworks.com/   <- web root IS the app
 *               ~/private/sps-config.php       <- one level up, outside. Fine.
 *
 *   folder      ~/public_html/sps/             <- app
 *               ~/public_html/private/         <- one level up, INSIDE the web
 *                                                 root. Anyone could fetch
 *                                                 /private/sps-config.php.
 *               ~/private/sps-config.php       <- two levels up, outside. Fine.
 *
 * A fixed "one level up" rule is correct for the first and hands out the
 * database password in the second. So the level is not fixed; the test is
 * whether the file is reachable over HTTP.
 */
function app_config(?string $key = null)
{
    static $cfg = null;

    if ($cfg === null) {
        $docroot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');

        $candidates = [];
        if ($env = getenv('SPS_CONFIG')) $candidates[] = $env;

        // Walk up from the app directory. Four levels is far more than either
        // layout needs and still cannot escape a hosting account.
        $dir = APP_ROOT;
        for ($i = 0; $i < 4; $i++) {
            $dir = dirname($dir);
            if ($dir === '' || $dir === '.' || $dir === dirname($dir)) break;
            $candidates[] = $dir . '/private/sps-config.php';
        }

        $candidates[] = APP_ROOT . '/lib/config.local.php';  // development only, gitignored

        $found = null;
        foreach ($candidates as $path) {
            if (!is_file($path)) continue;

            /* The development fallback lives inside the application on purpose —
               it is gitignored, denied by .htaccess, and only ever points at a
               local SQLite file. Everything else must be out of reach. */
            $isDevFallback = path_norm($path) === path_norm(APP_ROOT . '/lib/config.local.php');

            if (!$isDevFallback && $docroot !== '' && path_inside($path, $docroot)) {
                app_log('REFUSED config inside the web root: ' . $path);
                continue;
            }
            $found = $path;
            break;
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

        // Now — and only now — the safe default set at the top of this file can
        // be relaxed, because we finally know whether this is a development box.
        if (!empty($cfg['debug'])) @ini_set('display_errors', '1');
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

/**
 * A writable directory that is NOT reachable over HTTP.
 *
 * Same reasoning as the config search, for the same reason: a log of what went
 * wrong on a registration page quotes the request, and the request contains
 * somebody's name and email address. Serving that at /private/logs/app.log
 * would be a worse leak than the bug being logged.
 */
function app_private_dir(string $sub = ''): ?string
{
    static $base = null;

    if ($base === null) {
        $docroot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        $base    = false;
        $dir     = APP_ROOT;
        for ($i = 0; $i < 4; $i++) {
            $dir = dirname($dir);
            if ($dir === '' || $dir === '.' || $dir === dirname($dir)) break;
            $candidate = $dir . '/private';
            if (!is_dir($candidate)) continue;
            if ($docroot !== '' && path_inside($candidate, $docroot)) continue;
            $base = $candidate;
            break;
        }
    }

    if ($base === false) return null;
    if ($sub === '') return $base;

    $path = $base . '/' . $sub;
    if (!is_dir($path)) @mkdir($path, 0700, true);
    return is_dir($path) ? $path : null;
}

function app_log(string $line): void
{
    $dir = app_private_dir('logs') ?? sys_get_temp_dir();
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
 * The URL path this installation is mounted at: "/" or "/sps/".
 *
 * Derived rather than configured, so the folder can be renamed — or the site
 * moved to a subdomain of its own — without editing anything.
 */
function app_base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;

    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    if ($dir === '' || $dir === '.' || $dir === '/') return $base = '/';
    return $base = rtrim($dir, '/') . '/';
}

/**
 * Start a session with cookie flags set BEFORE the cookie is issued.
 *
 * Called explicitly rather than on every request: an anonymous visitor reading
 * the course catalogue should not be given a cookie they never needed.
 *
 * The path and the name both matter more in the folder layout than they would
 * on a subdomain. With four academies living at /sps/, /fungi/, /maziv/ and
 * /equinix/ on ONE hostname, a cookie set at path "/" is sent to all four —
 * one company's session cookie travelling to another company's site. Scoping
 * the cookie to this installation's own path, and giving each tenant its own
 * session name, keeps them apart.
 *
 * Worth being honest about the limit: a cookie path is not a security boundary.
 * Same host means same origin, and the browser will not defend these four from
 * each other the way it would defend four subdomains. That is the real cost of
 * the folder layout, and it is why this is a stop on the way to subdomains
 * rather than the destination.
 */
function app_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $https = (($_SERVER['HTTPS'] ?? '') === 'on')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => app_base_path(),
        'secure'   => $https,
        'httponly' => true,      // not readable from JavaScript
        'samesite' => 'Lax',     // survives normal navigation, blocks cross-site POST
    ]);

    $slug = (string) (app_config_safe('tenant') ?? 'acad');
    session_name('acad_' . (preg_replace('/[^a-z0-9]/', '', strtolower($slug)) ?: 'x'));
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
