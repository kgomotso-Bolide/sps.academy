<?php
declare(strict_types=1);

/* Push the shared academy back end from SPS to the other academies.
 *
 *   php tools/sync-backend.php --check                  what has drifted, change nothing
 *   php tools/sync-backend.php --check ../fungi         one target only
 *   php tools/sync-backend.php --apply ../fungi         write it
 *
 * WHY THIS EXISTS
 *
 * Four sites run one application. Before the chrome was extracted into
 * lib/chrome.php they could not share pages at all, because every page had the
 * client's name and logo baked into it. Now the only per-site PHP file is
 * lib/brand.php, and everything else can be — must be — identical.
 *
 * "Must be" is the part that needs a tool. Four copies that are allowed to
 * drift are not a shared core, they are four codebases with a common ancestor,
 * and the difference shows up the day a security fix lands in one of them. The
 * four copies of styles.css already proved this: Fungi's was 293 lines behind,
 * and nobody knew until it was measured.
 *
 * WHAT IT WILL NOT DO
 *
 *   - It never touches lib/brand.php, the site's own .html, .js, images, README
 *     or workflow. Those are the site.
 *   - It never touches a configuration file. Those are not in any repository.
 *   - It refuses to run from a dirty SPS working tree, so that whatever gets
 *     copied into three other repositories is something that exists in a commit
 *     here and can be traced back to it.
 *   - It does not commit or push. A human reads the diff in the target repo.
 */

$root   = dirname(__DIR__);            // the SPS repository — the source of truth
$argv   = $_SERVER['argv'] ?? [];
$mode   = '';
$only   = [];

foreach (array_slice($argv, 1) as $a) {
    if ($a === '--check' || $a === '--apply') { $mode = substr($a, 2); continue; }
    if ($a === '--force') { $force = true; continue; }
    $only[] = $a;
}
$force = $force ?? false;

if ($mode === '') {
    fwrite(STDERR, "usage: php tools/sync-backend.php --check|--apply [target ...] [--force]\n");
    exit(2);
}

/* ---------------------------------------------------------------------------
   The manifest.

   A file is on this list if its content is the application, and off it if its
   content is the client. There is no third category on purpose: "mostly shared
   with a few local tweaks" is how the four copies of styles.css got into the
   state they were in.
   --------------------------------------------------------------------------- */

$shared = [
    // The engine.
    'lib/audit.php', 'lib/auth.php', 'lib/bootstrap.php', 'lib/chrome.php',
    'lib/config.sample.php', 'lib/csrf.php', 'lib/db.php', 'lib/install.php',
    'lib/learner.php', 'lib/mail.php', 'lib/progress.php', 'lib/registration.php',
    'lib/reset.php', 'lib/.htaccess',

    // The database.
    'schema/schema.mysql.sql', 'schema/schema.sqlite.sql',

    // The pages. Every one of these is now brand-neutral; see lib/chrome.php.
    'account.php', 'admin.php', 'admin-progress.php', 'admin-users.php',
    'contact.php', 'forgot.php', 'login.php', 'logout.php', 'my.php',
    'phpcheck.php', 'pm-progress.php', 'privacy.php', 'reset.php', 'setup.php',

    // Client-side code that talks to the back end. profile.js and pm-progress.js
    // know the shape of account.php's JSON, so they are part of the application
    // and not part of the site — a stale copy of either is a broken sign-in.
    'profile.js', 'pm-progress.js',

    // Server configuration and the tools.
    '.htaccess',
    'tools/migrate.php', 'tools/make-user.php', 'tools/make-deploy-zip.php',
    'tools/dev-server.php',
];

/* Never synced, and listed explicitly so that adding a file to the wrong side
   of the line is a decision somebody made rather than an omission. */
$neverSync = [
    'lib/brand.php',        // the whole point: this is what makes a site itself
    'lib/config.local.php', // gitignored, developer's own, points at their SQLite
    'tools/sync-backend.php',
];

/* The one file that is neither wholly shared nor wholly local. Its palette
   belongs to the client and its component styles belong to the application, so
   the application's half lives between two markers and only that half moves. */
const CSS_FILE  = 'styles.css';
const CSS_BEGIN = '/* >>> SHARED ACADEMY STYLES — BEGIN';
const CSS_END   = '/* <<< SHARED ACADEMY STYLES — END';

/* ---------------------------------------------------------------------------
   Targets
   --------------------------------------------------------------------------- */

$defaultTargets = ['../fungi', '../equinix', '../maziv'];
$targets = $only ?: $defaultTargets;

/* ---------------------------------------------------------------------------
   Refuse to copy uncommitted work
   --------------------------------------------------------------------------- */

if ($mode === 'apply' && !$force) {
    $status = (string) shell_exec('cd ' . escapeshellarg($root) . ' && git status --porcelain 2>&1');

    /* Only the files this run would actually copy. An earlier version refused on
       any dirty file at all, which sounds safer and is not: it fires on a
       scratch directory somebody left lying around, so the habit it teaches is
       --force, and --force is the one thing that must stay rare. Narrow the
       question to "is what I am about to copy committed?" and the answer is
       almost always yes, so a refusal means something. */
    $dirty = [];
    foreach (explode("\n", $status) as $line) {
        $path = trim(substr($line, 2));
        if ($path === '') continue;
        if (str_contains($path, ' -> ')) $path = substr($path, strpos($path, ' -> ') + 4);
        $path = trim($path, '"');
        if (in_array($path, $shared, true) || $path === CSS_FILE) $dirty[] = trim($line);
    }

    if ($dirty) {
        fwrite(STDERR,
            "These are on the shared manifest and are not committed, so a sync now\n"
          . "would copy something that exists in no commit here and cannot be traced\n"
          . "back to one. Commit first, or pass --force if you know why you want this.\n\n"
          . '  ' . implode("\n  ", $dirty) . "\n\n");
        exit(1);
    }
}

/* ---------------------------------------------------------------------------
   Helpers
   --------------------------------------------------------------------------- */

/** Compare ignoring line endings only. The repositories disagree about CRLF and
    that is not drift worth reporting; anything else is. */
function same(string $a, string $b): bool
{
    return str_replace("\r\n", "\n", $a) === str_replace("\r\n", "\n", $b);
}

function css_region(string $css, string $where): ?string
{
    $i = strpos($css, CSS_BEGIN);
    $j = strpos($css, CSS_END);
    if ($i === false || $j === false || $j < $i) {
        fwrite(STDERR, "  ! $where: styles.css has no shared-styles markers\n");
        return null;
    }
    $end = strpos($css, "*/", $j);
    return substr($css, $i, ($end === false ? $j : $end + 2) - $i);
}

$verb  = $mode === 'apply' ? 'wrote' : 'would write';
$total = ['same' => 0, 'changed' => 0, 'new' => 0, 'missing' => 0];

/* ---------------------------------------------------------------------------
   Do it
   --------------------------------------------------------------------------- */

foreach ($targets as $t) {
    $dir = realpath(str_starts_with($t, '.') ? $root . '/' . $t : $t);
    if ($dir === false || !is_dir($dir)) {
        printf("\n%s — not there, skipped\n", $t);
        continue;
    }
    if (realpath($dir) === realpath($root)) {
        printf("\n%s — that is SPS itself, skipped\n", $t);
        continue;
    }

    printf("\n=== %s ===\n", $dir);

    /* A target without lib/brand.php has never been set up, and copying the
       application into it would give a site that fails on every page with
       "no lib/brand.php". Say so instead. */
    if (!is_file($dir . '/lib/brand.php')) {
        printf("  ! no lib/brand.php — set one up first (copy SPS's and change the nouns)\n");
    }

    foreach ($shared as $rel) {
        $src = $root . '/' . $rel;
        $dst = $dir . '/' . $rel;
        if (!is_file($src)) { printf("  ! MISSING IN SPS  %s\n", $rel); $total['missing']++; continue; }

        $new = (string) file_get_contents($src);
        $old = is_file($dst) ? (string) file_get_contents($dst) : null;

        if ($old !== null && same($old, $new)) { $total['same']++; continue; }

        $label = $old === null ? 'new    ' : 'changed';
        $total[$old === null ? 'new' : 'changed']++;
        printf("  %s  %s\n", $label, $rel);

        if ($mode === 'apply') {
            $d = dirname($dst);
            if (!is_dir($d)) mkdir($d, 0775, true);
            file_put_contents($dst, $new);
        }
    }

    /* --- the managed region of styles.css ------------------------------- */
    $srcCss = (string) file_get_contents($root . '/' . CSS_FILE);
    $region = css_region($srcCss, 'sps');
    $dstCssPath = $dir . '/' . CSS_FILE;

    if ($region !== null && is_file($dstCssPath)) {
        $dstCss = (string) file_get_contents($dstCssPath);
        $have   = css_region($dstCss, basename($dir));

        if ($have === null) {
            // First sync into this site: append the block.
            printf("  new      %s  (shared block appended)\n", CSS_FILE);
            $total['new']++;
            if ($mode === 'apply') {
                file_put_contents($dstCssPath, rtrim($dstCss) . "\n\n" . $region . "\n");
            }
        } elseif (!same($have, $region)) {
            printf("  changed  %s  (shared block, %d lines)\n",
                   CSS_FILE, substr_count($region, "\n"));
            $total['changed']++;
            if ($mode === 'apply') {
                file_put_contents($dstCssPath, str_replace($have, $region, $dstCss));
            }
        } else {
            $total['same']++;
        }
    }

    foreach ($neverSync as $rel) {
        if (is_file($dir . '/' . $rel)) printf("  kept     %s  (never synced)\n", $rel);
    }
}

printf("\n%d identical, %d %s, %d new, %d missing in SPS\n",
       $total['same'], $total['changed'], $verb, $total['new'], $total['missing']);

if ($mode === 'check' && ($total['changed'] || $total['new'])) exit(1);
