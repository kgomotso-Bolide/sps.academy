<?php
declare(strict_types=1);

/* Download a certificate handed in for recognition of prior learning.
 *
 * Built like poe-file.php: the PDF is never reachable by URL, and this checks
 * who is asking — the learner who handed it in, or an administrator, who is
 * the assessor. Anyone else gets nothing, and every refusal and every download
 * is audited.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/material_files.php';
require __DIR__ . '/lib/logbook.php';
require __DIR__ . '/lib/submissions.php';
require __DIR__ . '/lib/rpl.php';

$me = require_user();

$id  = (int) ($_GET['id'] ?? 0);
$row = $id > 0 ? db_optional(fn() => rpl_claim_get($id)) : null;

if ($row === null || empty($row['disk_name']) || !rpl_may_view($row, $me)) {
    audit('rpl.file_denied', 'rpl_claims', $id ?: null, (string) ($_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('That file is not available.');
}

audit('rpl.file_downloaded', 'rpl_claims', $id, (string) $row['course_slug']);
submission_stream($row, 'rpl-files');
