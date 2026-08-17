<?php
declare(strict_types=1);

/* Build a zip containing exactly what should go on the server, and nothing else.
 *
 *     php tools/make-deploy-zip.php
 *
 * WHY NOT JUST ZIP THE FOLDER. Two reasons, and both are the kind that are only
 * noticed afterwards:
 *
 *   1. This directory contains things that must never be on a public web
 *      server — _drafts/ holds client emails, lib/config.local.php holds the IP
 *      hashing key, and .git/ holds every version of everything. A zip of the
 *      folder uploads all of it. Nothing in .gitignore protects you here,
 *      because a zip is not git.
 *
 *   2. The site does not work without its .htaccess files, and they are exactly
 *      the files a hand-made zip is most likely to leave behind — a leading dot
 *      hides them in Explorer, and some archive tools skip them by default.
 *      Losing the root one breaks every link on the site AND removes the rule
 *      that stops /lib/ being browsable. That combination is much worse than an
 *      obviously broken site, because it looks like it half works.
 *
 * The archive holds files at the TOP LEVEL, not inside an sps/ folder, so
 * extracting it inside spsacademy/ gives spsacademy/index.html — not
 * spsacademy/sps/index.html.
 *
 * It refuses to write the file if any of its own checks fail.
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('zip')) { fwrite(STDERR, "PHP's zip extension is not available.\n"); exit(1); }

$root = dirname(__DIR__);
$out  = dirname($root) . '/sps-deploy-' . date('Y-m-d-Hi') . '.zip';

/* Directories that never ship, matched on the first path segment.
 *
 * tools/ is in this list on purpose. Every script in it is command-line only,
 * and Xneelo answers port 22 with "SSH-2.0-FTP Service" — SFTP, no shell — so
 * none of it could be run on the server even if it were there. Uploading code
 * that cannot run, to a public web server, is all risk and no benefit. It is
 * also why setup.php exists.
 *
 * schema/ DOES ship, and must: setup.php reads schema/schema.mysql.sql to
 * create the tables. It is denied to the web by its own .htaccess. */
$skipDirs = [
    '.git', '.github', '.vscode', '.claude', '.idea',
    '_drafts', 'private', 'node_modules', 'tools',
    'Website-inspiration-latout',
    'SPS-AI-Micro-Learning-Onepager.files',
];

/* Files that never ship. The config is the important one — it carries the IP
   pepper, and on the server the real config lives outside the web root.
   The git housekeeping files are only noise on a web server, but .gitignore in
   particular advertises that lib/config.local.php exists, which is a small hint
   nobody needs to be given. */
$skipFiles = [
    'lib/config.local.php',
    'lib/config.sample.php',   // a template, read here and filled in by hand — the
                               // server never loads it, so it has no business there
    '.gitignore',
    '.gitattributes',
];

/* Working documents rather than site content. Checked against the whole
   relative path, so resources/*.pdf (which the course pages link to) still
   ships — only the loose one-pagers at the top level are dropped. */
$skipPattern = '#^(.*\.(md|zip|docx|htm)|SPS-AI-Micro-Learning-Onepager\..*)$#i';

/* Every .htaccess that ships is load-bearing, and the archive is not valid
   without all of them. tools/.htaccess is not listed because tools/ does not
   ship at all — there is nothing left in it to deny. */
$required = ['.htaccess', 'lib/.htaccess', 'schema/.htaccess'];

/* Files the server genuinely cannot work without, checked for the same reason:
   a silently incomplete archive is worse than an obviously broken one. */
$requiredAlso = [
    'index.html', 'styles.css', 'contact.php', 'setup.php',
    'lib/bootstrap.php', 'lib/db.php', 'lib/install.php',
    'schema/schema.mysql.sql',
];

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $path) {
    $rel = str_replace('\\', '/', substr($path->getPathname(), strlen($root) + 1));
    if ($rel === '') continue;

    $first = explode('/', $rel)[0];
    if (in_array($first, $skipDirs, true)) continue;
    if ($path->isDir()) continue;
    if (in_array($rel, $skipFiles, true)) continue;
    if (preg_match($skipPattern, $rel)) continue;

    $files[] = $rel;
}
sort($files);

/* ---------------------------------------------------------------------------
   Checks, before anything is written
   --------------------------------------------------------------------------- */

$problems = [];

foreach (array_merge($required, $requiredAlso) as $r) {
    if (!in_array($r, $files, true)) $problems[] = 'missing ' . $r;
}
foreach ($files as $f) {
    if (basename($f) === 'config.local.php') $problems[] = 'config.local.php would have shipped';
    if (str_starts_with($f, '_drafts/'))     $problems[] = 'a draft would have shipped: ' . $f;
}

/* The development pepper must not appear anywhere in the archive. This catches
   the case where it has been pasted into some other file while debugging. */
$devConfig = $root . '/lib/config.local.php';
if (is_file($devConfig)) {
    $cfg = @include $devConfig;
    $pepper = is_array($cfg) ? (string) ($cfg['ip_pepper'] ?? '') : '';
    if (strlen($pepper) > 16) {
        foreach ($files as $f) {
            $full = $root . '/' . $f;
            if (filesize($full) > 2_000_000) continue;
            if (!preg_match('#\.(php|html|js|css|json|txt|sql|htaccess)$#i', $f)
                && basename($f) !== '.htaccess') continue;
            if (str_contains((string) file_get_contents($full), $pepper)) {
                $problems[] = 'the development pepper appears in ' . $f;
            }
        }
    }
}

if ($problems) {
    fwrite(STDERR, "REFUSING to build the archive:\n");
    foreach ($problems as $p) fwrite(STDERR, '  - ' . $p . "\n");
    exit(1);
}

/* ---------------------------------------------------------------------------
   Write
   --------------------------------------------------------------------------- */

$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not create $out\n");
    exit(1);
}
foreach ($files as $f) $zip->addFile($root . '/' . $f, $f);
$zip->close();

$bytes = filesize($out);
printf("\n  built    %s\n", str_replace('\\', '/', $out));
printf("  files    %d\n", count($files));
printf("  size     %.1f MB\n", $bytes / 1048576);

echo "\n  included, and easy to lose:\n";
foreach ($required as $r) echo "    $r\n";

echo "\n  deliberately left out:\n";
echo "    tools/                 command-line only; Xneelo has no shell\n";
echo "    lib/config.sample.php  a template, filled in by hand and never loaded\n";
echo "    _drafts/               client emails\n";
echo "    .git/                  every version of everything\n";
echo "    *.md, the Word one-pagers, .gitignore\n";

echo "\n  Extract this INSIDE the spsacademy folder — the files sit at the top\n";
echo "  level of the archive, so you should end up with spsacademy/index.html\n";
echo "  and not spsacademy/sps/index.html.\n\n";
