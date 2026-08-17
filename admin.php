<?php
declare(strict_types=1);

/* The registrations list.
 *
 * This page is not a nice-to-have. Moving registrations out of an inbox and
 * into a database takes something away from whoever was reading that inbox, and
 * until this exists they have strictly less than they had before. Everything
 * here is in service of one question — who has registered, and what has been
 * done about them — and nothing else is on the page.
 *
 * Every view is audited with the number of records reached, and every export is
 * audited separately. Under POPIA the interesting question after an incident is
 * not "was there a login" but "what was read", and a list page is where bulk
 * reading actually happens.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';

$me = require_admin();

const STATUSES  = ['new', 'contacted', 'enrolled', 'declined'];
const PER_PAGE  = 25;

/* ---------------------------------------------------------------------------
   Changing a status
   --------------------------------------------------------------------------- */

$notice = '';

if (is_post()) {
    if (!csrf_valid()) {
        $notice = 'That form had expired — nothing was changed. Please try again.';
    } else {
        $id     = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if ($id > 0 && in_array($status, STATUSES, true)) {
            // Scoped to the tenant in the WHERE clause, not just in the lookup:
            // an id from another company must not be updatable by guessing it.
            $n = db_run(
                'UPDATE registrations SET status = ? WHERE id = ? AND tenant_id = ?',
                [$status, $id, tenant_id()]
            )->rowCount();

            if ($n > 0) {
                audit('registration.status_changed', 'registrations', $id, 'to: ' . $status);
                $notice = 'Registration #' . $id . ' marked "' . $status . '".';
            }
        }
        csrf_rotate();
    }
}

/* ---------------------------------------------------------------------------
   Filtering
   --------------------------------------------------------------------------- */

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));

$where  = ['tenant_id = ?'];
$params = [tenant_id()];

if ($q !== '') {
    $where[]  = '(full_name LIKE ? OR email LIKE ? OR employee_no LIKE ? OR course_title LIKE ?)';
    $like     = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($status, STATUSES, true)) {
    $where[]  = 'status = ?';
    $params[] = $status;
}

$sqlWhere = ' WHERE ' . implode(' AND ', $where);
$total    = (int) db_value('SELECT COUNT(*) FROM registrations' . $sqlWhere, $params);
$pages    = max(1, (int) ceil($total / PER_PAGE));
$page     = min($page, $pages);

/* ---------------------------------------------------------------------------
   Export

   Handled before any output so the headers are still ours to set. The export
   ignores pagination on purpose — a spreadsheet of the first 25 rows is a trap.
   --------------------------------------------------------------------------- */

if (($_GET['export'] ?? '') === 'csv') {
    $rows = db_all(
        'SELECT id, created_at, full_name, email, phone, employee_no, department,
                line_manager, course_title, status, message
           FROM registrations' . $sqlWhere . ' ORDER BY id DESC',
        $params
    );

    audit('registrations.exported', 'registrations', null,
          count($rows) . ' rows, filter: ' . ($q !== '' ? 'q=' . $q . ' ' : '')
          . ($status !== '' ? 'status=' . $status : 'all'));

    $name = 'sps-registrations-' . gmdate('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');

    $out = fopen('php://output', 'w');
    // Excel reads a UTF-8 CSV as the local codepage unless it sees a BOM, which
    // is how "Renée" becomes "RenÃ©e" in a file that is perfectly correct.
    fwrite($out, "\xEF\xBB\xBF");

    /* The fifth argument — $escape — is passed explicitly and empty on purpose.
       PHP's historical default is a backslash escape that is not part of the CSV
       format and that Excel does not understand, and from PHP 8.4 leaving it out
       raises a deprecation notice. On a page that is streaming a file, a notice
       is not a log line: it is written into the download, and the spreadsheet
       arrives with PHP warnings in the first cells. */
    $put = static function ($handle, array $row): void {
        fputcsv($handle, $row, ',', '"', '');
    };

    $put($out, ['ID', 'Received (UTC)', 'Name', 'Email', 'Phone', 'Employee no',
                'Department', 'Line manager', 'Course', 'Status', 'Message']);
    foreach ($rows as $r) $put($out, array_values($r));
    fclose($out);
    exit;
}

$rows = db_all(
    'SELECT * FROM registrations' . $sqlWhere . ' ORDER BY id DESC LIMIT ' . PER_PAGE
    . ' OFFSET ' . (($page - 1) * PER_PAGE),
    $params
);

audit('registrations.viewed', 'registrations', null,
      count($rows) . ' of ' . $total . ' shown');

$counts = [];
foreach (db_all('SELECT status, COUNT(*) AS n FROM registrations WHERE tenant_id = ? GROUP BY status',
                [tenant_id()]) as $r) {
    $counts[$r['status']] = (int) $r['n'];
}

/** Keep the current filter when building a link. */
function qs(array $over = []): string
{
    $base = array_filter([
        'q'      => $_GET['q']      ?? '',
        'status' => $_GET['status'] ?? '',
        'page'   => $_GET['page']   ?? '',
    ], fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query(array_merge($base, $over));
}

function when(string $utc): string
{
    // Stored in UTC, read in Johannesburg. Two hours is not a rounding error
    // when someone is checking whether a registration came in before a meeting.
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('Africa/Johannesburg'));
    return $d->format('d M Y, H:i');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrations — SPS Academy</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<nav id="nav">
  <div class="nav-inner">
    <a href="./" class="brand"><img src="sps-dark-logo.svg" alt="SPS — Sustainable Power Solutions"></a>
    <div class="nav-links">
      <a href="admin" class="active">Registrations</a>
      <a href="./">View the site</a>
      <form method="POST" action="logout" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="linkish">Sign out (<?= e($me['first_name']) ?>)</button>
      </form>
    </div>
  </div>
</nav>

<section class="section page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Academy administration</span>
        <h2>Registrations</h2>
      </div>
      <a class="btn btn-ghost" href="<?= e(qs(['export' => 'csv'])) ?>">Download as CSV</a>
    </div>

    <?php if ($notice !== ''): ?>
      <p class="adm-notice" role="status"><?= e($notice) ?></p>
    <?php endif; ?>

    <div class="adm-tabs">
      <a href="<?= e(qs(['status' => '', 'page' => 1])) ?>"<?= $status === '' ? ' class="on"' : '' ?>>
        All <span><?= array_sum($counts) ?></span></a>
      <?php foreach (STATUSES as $s): ?>
        <a href="<?= e(qs(['status' => $s, 'page' => 1])) ?>"<?= $status === $s ? ' class="on"' : '' ?>>
          <?= e(ucfirst($s)) ?> <span><?= (int) ($counts[$s] ?? 0) ?></span></a>
      <?php endforeach; ?>
    </div>

    <form class="adm-search" method="GET">
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, employee number or course">
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($q !== ''): ?><a class="adm-clear" href="<?= e(qs(['q' => '', 'page' => 1])) ?>">Clear</a><?php endif; ?>
    </form>

    <?php if (!$rows): ?>
      <p class="adm-empty">
        <?= $total === 0 && $q === '' && $status === ''
              ? 'No registrations yet. They will appear here the moment someone sends the form on the Contact page.'
              : 'Nothing matches that filter.' ?>
      </p>
    <?php else: ?>
      <div class="adm-scroll">
      <table class="adm-table">
        <thead><tr>
          <th>#</th><th>Received</th><th>Name</th><th>Contact</th>
          <th>Course</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="adm-id"><?= (int) $r['id'] ?></td>
            <td class="adm-when"><?= e(when($r['created_at'])) ?></td>
            <td>
              <strong><?= e($r['full_name']) ?></strong>
              <?php if ($r['employee_no']): ?><span class="adm-sub"><?= e($r['employee_no']) ?></span><?php endif; ?>
              <?php if ($r['department']): ?><span class="adm-sub"><?= e($r['department']) ?></span><?php endif; ?>
              <?php if ($r['line_manager']): ?><span class="adm-sub">Manager: <?= e($r['line_manager']) ?></span><?php endif; ?>
            </td>
            <td>
              <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a>
              <?php if ($r['phone']): ?><span class="adm-sub"><a href="tel:<?= e($r['phone']) ?>"><?= e($r['phone']) ?></a></span><?php endif; ?>
            </td>
            <td>
              <?= $r['course_title'] ? e($r['course_title']) : '<span class="adm-none">not specified</span>' ?>
              <?php if ($r['message']): ?><span class="adm-msg"><?= e($r['message']) ?></span><?php endif; ?>
            </td>
            <td>
              <form method="POST" class="adm-status">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <select name="status" onchange="this.form.submit()" aria-label="Status of registration <?= (int) $r['id'] ?>">
                  <?php foreach (STATUSES as $s): ?>
                    <option value="<?= e($s) ?>"<?= $r['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                  <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-ghost">Save</button></noscript>
              </form>
              <span class="adm-sub">Delete after <?= e((string) $r['purge_after']) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>

      <?php if ($pages > 1): ?>
        <div class="adm-pages">
          <?php if ($page > 1): ?><a href="<?= e(qs(['page' => $page - 1])) ?>">← Newer</a><?php endif; ?>
          <span>Page <?= $page ?> of <?= $pages ?> · <?= $total ?> registrations</span>
          <?php if ($page < $pages): ?><a href="<?= e(qs(['page' => $page + 1])) ?>">Older →</a><?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <p class="adm-foot">These are people's personal details. Take out of here only what you
      need for the job in front of you, and delete any spreadsheet you export when you are
      finished with it — a copy on a laptop is outside everything this system does to protect
      it. Every view and every export on this page is logged.</p>

  </div>
</section>
<script src="site.js"></script>
</body></html>
