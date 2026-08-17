<?php
/* A deliberately tiny preflight check. DELETE IT once the site is working.
 *
 * Its whole job is to answer one question before anything else is investigated:
 * does PHP run on this server at all? It depends on nothing — not lib/, not the
 * configuration file, not the database — so if this page works and the rest of
 * the site does not, the fault is ours; and if this page does not work either,
 * the fault is the hosting and no amount of editing our code will help.
 *
 * Deliberately NOT phpinfo(). That publishes paths, module lists and
 * environment variables to anyone who finds the URL. This prints the few facts
 * that matter and nothing else.
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$need = ['pdo', 'pdo_mysql', 'mbstring', 'session', 'json', 'fileinfo'];

echo "PHP is running.\n\n";
printf("  version        %s\n", PHP_VERSION);
printf("  interface      %s\n", PHP_SAPI);
printf("  version is ok  %s\n", PHP_VERSION_ID >= 80000 ? 'yes' : 'NO — this site needs PHP 8.0 or newer');

echo "\n  extensions:\n";
$missing = [];
foreach ($need as $e) {
    $ok = extension_loaded($e);
    if (!$ok) $missing[] = $e;
    printf("    %-12s %s\n", $e, $ok ? 'yes' : 'MISSING');
}

/* Where the application would look for its configuration, and whether it found
   one. The path is shown because getting it wrong is the most common mistake
   here; the contents are never touched. */
$appRoot = __DIR__;
$docroot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');

/* The paths as PHP actually sees them. On this host public_html is a SYMLINK,
   so the directory the SFTP client shows and the directory PHP resolves are not
   the same place — which is exactly how a configuration file ends up sitting in
   plain view somewhere the application will never look. */
echo "\n  paths as PHP resolves them:\n";
printf("    this file          %s\n", __DIR__);
printf("    document root      %s\n", $docroot !== '' ? $docroot : '(not set)');
printf("    real document root %s\n", $docroot !== '' ? (realpath($docroot) ?: '(cannot resolve)') : '-');
printf("    one level up       %s\n", dirname($appRoot));
printf("    two levels up      %s\n", dirname($appRoot, 2));

/* Where the account's real home is, which is NOT reachable by walking up from
   the site when public_html is a symlink. This is the directory an SFTP client
   shows on login, and therefore the obvious place to put a config file. */
$home = (string) (getenv('HOME') ?: '');
if ($home === '' && function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
    $pw = @posix_getpwuid(posix_getuid());
    $home = (string) ($pw['dir'] ?? '');
}
printf("    account home       %s\n", $home !== '' ? $home : '(cannot determine)');

$basedir = (string) ini_get('open_basedir');
printf("    open_basedir       %s\n", $basedir !== '' ? $basedir : '(not set — PHP may read anywhere it has permission)');

/* Can PHP actually reach the home directory, and is the config sitting there?
   This is the question that decides where the file has to live. */
if ($home !== '') {
    printf("    home is readable   %s\n", is_readable($home) ? 'yes' : 'NO');
    foreach ([$home . '/sps-config.php', $home . '/private/sps-config.php'] as $c) {
        printf("    %-18s %s\n", basename(dirname($c)) === basename($home) ? 'config here?' : 'config in private?',
            is_file($c) ? 'FOUND: ' . $c : 'not at ' . $c);
    }
}

echo "\n  configuration:\n";
$found   = null;
$dir     = $appRoot;
for ($i = 0; $i < 4; $i++) {
    $dir = dirname($dir);
    if ($dir === '' || $dir === '.' || $dir === dirname($dir)) break;
    foreach ([$dir . '/private/sps-config.php', $dir . '/sps-config.php'] as $candidate) {
        $inside = $docroot !== '' && str_starts_with(
            str_replace('\\', '/', (string) (realpath($candidate) ?: $candidate)),
            rtrim(str_replace('\\', '/', (string) (realpath($docroot) ?: $docroot)), '/') . '/'
        );
        printf("    %-56s %s\n", $candidate,
            !is_file($candidate) ? 'not there'
                : ($inside ? 'FOUND but INSIDE the web root — refused' : 'found, and outside the web root'));
        if (is_file($candidate) && !$inside && $found === null) $found = $candidate;
    }
}
printf("    %s\n", $found ? 'A usable configuration file was found.'
                          : 'No usable configuration file. See DEPLOY-XNEELO.md step 4.');

/* Can we actually reach the database?
 *
 * Worth testing here rather than discovering it through setup.php, because with
 * debug off a failed connection shows the visitor "something went wrong" and
 * nothing else — correct for a learner, useless for whoever is installing.
 *
 * The exception message is NOT printed. It contains the DSN, which carries the
 * host and database name; the failure is classified instead. */
if ($found !== null) {
    echo "\n  database:\n";
    $cfg = @include $found;
    $db  = is_array($cfg) ? ($cfg['db'] ?? []) : [];
    printf("    driver           %s\n", $db['driver'] ?? '(not set)');
    printf("    database name    %s\n", ($db['name'] ?? '') !== '' ? 'set' : 'NOT SET');
    printf("    user             %s\n", ($db['user'] ?? '') !== '' ? 'set' : 'NOT SET');
    printf("    password         %s\n", ($db['pass'] ?? '') !== '' ? 'set' : 'NOT SET');
    printf("    setup_token      %s\n", ($cfg['setup_token'] ?? '') !== ''
        ? 'set — /setup is reachable' : 'empty — /setup is a 404 (set it to install)');
    printf("    ip_pepper        %s\n", strlen((string) ($cfg['ip_pepper'] ?? '')) >= 32
        ? 'set' : 'NOT SET or too short — the site will refuse to store anything');

    try {
        $dsn = ($db['driver'] ?? 'mysql') === 'sqlite'
            ? 'sqlite:' . ($db['path'] ?? '')
            : sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'] ?? 'localhost', $db['name'] ?? '');
        $pdo = new PDO($dsn, $db['user'] ?? null, $db['pass'] ?? null,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        echo "    connection       CONNECTED\n";
        $tables = [];
        foreach (['tenants', 'users', 'registrations', 'consents', 'audit_log', 'progress_reports'] as $t) {
            try { $pdo->query('SELECT 1 FROM ' . $t . ' LIMIT 1'); $tables[] = $t; } catch (Throwable $e) {}
        }
        printf("    tables           %s\n", $tables
            ? count($tables) . ' of 6 present (' . implode(', ', $tables) . ')'
            : 'none yet — run /setup');
    } catch (Throwable $e) {
        $m = $e->getMessage();
        $why = str_contains($m, 'Access denied') ? 'the username or password is wrong'
             : (str_contains($m, 'Unknown database') ? 'that database does not exist'
             : (str_contains($m, 'No such file') || str_contains($m, 'refused') ? 'no database server answered at that host'
             : 'see the site log for the full message'));
        echo "    connection       FAILED — " . $why . "\n";
    }
}

echo "\n  files:\n";
foreach (['.htaccess', 'lib/bootstrap.php', 'schema/schema.mysql.sql', 'setup.php'] as $f) {
    printf("    %-26s %s\n", $f, is_file($appRoot . '/' . $f) ? 'present' : 'MISSING');
}

echo "\n";
echo $missing
    ? "Missing extensions must be enabled before the site will work.\n"
    : "Nothing is missing. Delete this file once the site is up.\n";
