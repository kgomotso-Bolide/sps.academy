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

echo "\n  files:\n";
foreach (['.htaccess', 'lib/bootstrap.php', 'schema/schema.mysql.sql', 'setup.php'] as $f) {
    printf("    %-26s %s\n", $f, is_file($appRoot . '/' . $f) ? 'present' : 'MISSING');
}

echo "\n";
echo $missing
    ? "Missing extensions must be enabled before the site will work.\n"
    : "Nothing is missing. Delete this file once the site is up.\n";
