<?php
/* Router for PHP's built-in server, so the site can be run on a laptop.
 *
 *     php -S localhost:8080 -t . tools/dev-server.php
 *
 * The built-in server does not read .htaccess, so without this every
 * extensionless link — which is every link on the site — would 404 locally and
 * work in production, or the reverse. This file exists to make the two behave
 * the same, and it deliberately mirrors the rules in .htaccess in the same
 * order: real file, then .html, then .php.
 *
 * Not a production component. It is inside tools/, which is denied to the web.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = '/' . ltrim(rawurldecode($path), '/');

// Mirror the RedirectMatch 404 rules — server code is not a document, locally
// either. Getting this wrong here would hide the mistake until deployment.
if (preg_match('#^/(lib|schema|tools|_drafts|private)(/|$)#', $path)) {
    http_response_code(404);
    echo 'Not found.';
    return true;
}

if ($path === '/' || substr($path, -1) === '/') {
    $path .= 'index.html';
}

$file = $root . $path;

// A real file: let the built-in server deal with it (css, js, images, .php).
if (is_file($file)) {
    return false;
}

foreach (['.html', '.php'] as $ext) {
    if (is_file($file . $ext)) {
        $_SERVER['SCRIPT_NAME']     = $path . $ext;
        $_SERVER['SCRIPT_FILENAME'] = $file . $ext;
        if ($ext === '.php') {
            require $file . $ext;
            return true;
        }
        header('Content-Type: text/html; charset=utf-8');
        readfile($file . $ext);
        return true;
    }
}

http_response_code(404);
$notFound = $root . '/404.html';
if (is_file($notFound)) { readfile($notFound); return true; }
echo 'Not found: ' . htmlspecialchars($path);
return true;
