<?php
declare(strict_types=1);

/* Database access.
 *
 * MySQL in production; SQLite is supported so the whole application can be run
 * and tested on a laptop with no database server installed. That is not a
 * convenience — it is why this code could be finished and exercised before the
 * subdomains and the MySQL database existed at all.
 *
 * The two are kept honestly interchangeable by writing plain portable SQL:
 * no MySQL-only functions, no backtick quoting, placeholders everywhere.
 */

defined('APP_BOOTED') or exit('lib/db.php is not a page.');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $cfg    = app_config('db') ?? [];
    $driver = $cfg['driver'] ?? 'mysql';

    if ($driver === 'sqlite') {
        $dsn  = 'sqlite:' . ($cfg['path'] ?? (dirname(APP_ROOT) . '/private/dev.sqlite'));
        $user = null;
        $pass = null;
    } else {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $cfg['host'] ?? 'localhost',
            $cfg['name'] ?? ''
        );
        $user = $cfg['user'] ?? '';
        $pass = $cfg['pass'] ?? '';
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Real prepared statements, not strings glued together client-side.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // The message carries the DSN, and the DSN carries the database name
        // and host. It goes to the log, never to the page.
        app_fail('Database connection failed: ' . $e->getMessage());
    }

    if ($driver === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}

function db_driver(): string
{
    return (string) (app_config('db.driver') ?? 'mysql');
}

/* --------------------------------------------------------------------------
   Thin query helpers. Every one of these takes parameters separately; there is
   no function in this file that accepts an interpolated SQL string.
   -------------------------------------------------------------------------- */

function db_run(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [])
{
    $row = db_run($sql, $params)->fetch(PDO::FETCH_NUM);
    return $row === false ? null : $row[0];
}

function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql  = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
          . implode(', ', array_map(fn($c) => ':' . $c, $cols)) . ')';
    db_run($sql, $data);
    return (int) db()->lastInsertId();
}

/* --------------------------------------------------------------------------
   Tenancy

   One database, one row per company, a tenant_id on everything that holds
   personal information. The id is resolved once from the slug in the config
   file — not from the hostname, not from anything the client can influence.
   -------------------------------------------------------------------------- */

function tenant_id(): int
{
    static $id = null;
    if ($id !== null) return $id;

    $slug = (string) (app_config('tenant') ?? '');
    if ($slug === '') app_fail('No tenant set in the configuration file.');

    $found = db_value('SELECT id FROM tenants WHERE slug = ?', [$slug]);
    if ($found === null) {
        app_fail('Tenant "' . $slug . '" is not in the tenants table. Run tools/migrate.php.');
    }
    return $id = (int) $found;
}

function tenant(): array
{
    static $row = null;
    if ($row === null) {
        $row = db_one('SELECT * FROM tenants WHERE id = ?', [tenant_id()]) ?? [];
    }
    return $row;
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}
