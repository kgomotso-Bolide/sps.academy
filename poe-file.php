<?php
declare(strict_types=1);

/* Download a submitted workbook.
 *
 * The file is never reachable by URL. This checks who is asking: the learner
 * who uploaded it, an administrator, or a facilitator on that course. Anyone
 * else, signed in or not, gets nothing, and every refusal and every download
 * is audited.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/material_files.php';
require __DIR__ . '/lib/logbook.php';
require __DIR__ . '/lib/submissions.php';

$me = require_user();

$id  = (int) ($_GET['id'] ?? 0);
$row = $id > 0 ? db_optional(fn() => submission_file_get($id)) : null;

if ($row === null || !submission_may_view($row, $me)) {
    audit('poe.file_denied', 'poe_files', $id ?: null, (string) ($_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('That file is not available.');
}

audit('poe.file_downloaded', 'poe_files', $id, (string) $row['course_slug'] . ' ' . (string) $row['module_code']);
submission_stream($row);
