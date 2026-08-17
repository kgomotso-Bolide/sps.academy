<?php
declare(strict_types=1);

/* Create the tables and seed the tenant rows.
 *
 *     php tools/migrate.php            create/update, then report
 *     php tools/migrate.php --check    compare the two schema files only
 *
 * CLI only. It is also behind a deny rule in .htaccess and one in tools/, but
 * the guard below is the one that does not depend on the web server reading a
 * configuration file correctly.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/db.php';

$checkOnly = in_array('--check', $argv, true);

/* ---------------------------------------------------------------------------
   Drift check

   Two schema files, one for MySQL and one for SQLite, is a duplication we
   accepted so that both can be read as ordinary SQL by whoever inherits this.
   The cost of that choice is that they can disagree. This is the check that
   makes the cost affordable: a column added to one and forgotten in the other
   fails here, loudly, instead of failing in production six weeks later on the
   one INSERT that names it.
   --------------------------------------------------------------------------- */

/** @return array<string,string[]> table => column names, in file order */
function parse_schema(string $path): array
{
    $sql = file_get_contents($path);
    if ($sql === false) app_fail('Cannot read ' . $path);

    $sql = preg_replace('/^\s*--.*$/m', '', $sql);          // strip comments
    $tables = [];

    preg_match_all(
        '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?\s*\((.*?)\n\)/is',
        $sql,
        $matches,
        PREG_SET_ORDER
    );

    $notAColumn = ['PRIMARY', 'UNIQUE', 'KEY', 'CONSTRAINT', 'FOREIGN', 'INDEX', 'CHECK'];

    foreach ($matches as $m) {
        $cols = [];
        foreach (explode("\n", $m[2]) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $first = strtoupper(strtok($line, " \t("));
            if (in_array($first, $notAColumn, true)) continue;
            $name = trim(strtok($line, " \t"), '`", ');
            if ($name !== '') $cols[] = $name;
        }
        $tables[$m[1]] = $cols;
    }
    return $tables;
}

$root   = dirname(__DIR__) . '/schema/';
$mysql  = parse_schema($root . 'schema.mysql.sql');
$sqlite = parse_schema($root . 'schema.sqlite.sql');

$problems = [];

foreach (array_diff(array_keys($mysql), array_keys($sqlite)) as $t) {
    $problems[] = "table '$t' is in MySQL but not SQLite";
}
foreach (array_diff(array_keys($sqlite), array_keys($mysql)) as $t) {
    $problems[] = "table '$t' is in SQLite but not MySQL";
}
foreach ($mysql as $table => $cols) {
    if (!isset($sqlite[$table])) continue;
    foreach (array_diff($cols, $sqlite[$table]) as $c) {
        $problems[] = "$table.$c is in MySQL but not SQLite";
    }
    foreach (array_diff($sqlite[$table], $cols) as $c) {
        $problems[] = "$table.$c is in SQLite but not MySQL";
    }
    if ($cols !== $sqlite[$table] && !array_diff($cols, $sqlite[$table])
        && !array_diff($sqlite[$table], $cols)) {
        $problems[] = "$table has the same columns in a different order";
    }
}

$tableCount = count($mysql);
$colCount   = array_sum(array_map('count', $mysql));

if ($problems) {
    echo "SCHEMA DRIFT — the two schema files disagree:\n";
    foreach ($problems as $p) echo "  - $p\n";
    exit(1);
}
echo "schema  ok — $tableCount tables, $colCount columns, both files agree\n";

if ($checkOnly) exit(0);

/* ---------------------------------------------------------------------------
   Apply
   --------------------------------------------------------------------------- */

$driver = db_driver();
$file   = $root . 'schema.' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql';
echo "driver  $driver\n";
echo "config  " . app_config('_path') . "\n";

$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));

$applied = 0;
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    db()->exec($statement);
    $applied++;
}
echo "applied $applied statements\n";

/* Seed all four companies, not just this one.

   The tenants table describes the platform, not the installation. Every
   installation runs the same schema against the same database and picks its own
   row by slug, so the row for Fungi existing here is what makes standing Fungi
   up later a configuration change rather than a migration. */
$seed = [
    ['sps',     'SPS — Sustainable Power Solutions', 'SPS Academy'],
    ['fungi',   'Fungi Utilities (Pty) Ltd',         'Fungi Academy'],
    ['equinix', 'Equinix',                           'Equinix Academy'],
    ['maziv',   'Maziv',                             'Maziv Academy'],
];

$contact = (string) (app_config('notify_email') ?? 'kgomotso@centenarynetworks.com');
$added   = 0;

foreach ($seed as [$slug, $name, $academy]) {
    $exists = db_value('SELECT id FROM tenants WHERE slug = ?', [$slug]);
    if ($exists !== null) continue;
    db_insert('tenants', [
        'slug'          => $slug,
        'name'          => $name,
        'academy_name'  => $academy,
        'contact_email' => $contact,
        'created_at'    => now(),
    ]);
    $added++;
}
echo "tenants $added added, " . db_value('SELECT COUNT(*) FROM tenants') . " total\n";

$slug = (string) app_config('tenant');
echo "this installation serves '$slug' (tenant id " . tenant_id() . ")\n";
echo "done\n";
