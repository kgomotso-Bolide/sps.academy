<?php
declare(strict_types=1);

/* Submit your workbooks.
 *
 * The learner downloads the workbook (or practical workbook) for a module,
 * completes it as a Word document, and uploads it here. What they upload, and
 * what happens next, is lib/submissions.php. The logbook is not on this page:
 * it is typed on logbook.php, printed and signed.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/logbook.php';
require __DIR__ . '/lib/materials.php';
require __DIR__ . '/lib/material_files.php';
require __DIR__ . '/lib/submissions.php';
require __DIR__ . '/lib/chrome.php';

$me = require_user();

$notice = '';
$error  = '';

$enrolments = db_optional(fn() => learner_enrolments((int) $me['id']), []) ?: [];
$mine = array_values(array_filter(
    array_map(fn($e) => (string) $e['course_slug'], $enrolments),
    'submission_course_takes'
));

$course = (string) ($_GET['c'] ?? $_POST['c'] ?? '');
if ($course === '' || !in_array($course, $mine, true)) $course = $mine[0] ?? '';

if ($course !== '' && is_post()) {
    if (!csrf_valid()) {
        $error = 'This page had been open a while and the form expired. Nothing was uploaded, so please try again.';
    } elseif ((string) ($_POST['do'] ?? '') === 'upload') {
        $res = db_optional(fn() => submission_store(
            (int) $me['id'], $course,
            (string) ($_POST['code'] ?? ''), (string) ($_POST['kind'] ?? ''),
            $_FILES['file'] ?? []
        ), null);
        if ($res === null) {
            $error = db_schema_notice();
        } else {
            [$ok, $msg] = $res;
            $ok ? $notice = $msg : $error = $msg;
        }
        csrf_rotate();
    }
}

$slots   = $course !== '' ? (db_optional(fn() => submission_slots($course), []) ?: []) : [];
$history = $course !== '' ? (db_optional(fn() => submission_history((int) $me['id'], $course), []) ?: []) : [];
$poe     = $course !== '' ? (db_optional(fn() => poe_for_learner((int) $me['id'], $course), []) ?: []) : [];
$material = $course !== '' ? (db_optional(fn() => materials_slots_for_course($course), []) ?: []) : [];

$done = 0;
foreach ($slots as $s) if (isset($history[$s['code'] . ':' . $s['kind']])) $done++;

audit('submit.viewed', 'poe_files', (int) $me['id'], $course);

$statusClass = ['handed_in' => 'poe-handed_in', 'assessed' => 'poe-assessed', 'returned' => 'poe-returned'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit my workbooks — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'submit', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<section class="section-dark page-top">
  <div class="wrap">
    <div class="sec-head sec-head-wide">
      <span class="eyebrow">Portfolio of evidence</span>
      <h2 class="lede-h">Submit my workbooks</h2>
      <p>Every module has a workbook, and every practical module has a practical workbook. Download
        it, complete it in Word, save it, and upload it here. They are part of your portfolio of
        evidence, so your facilitator reads them and can send one back to you to improve.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>
    <?php if ($notice !== ''): ?><p class="adm-notice" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error  !== ''): ?><p class="form-err"   role="alert"><?= e($error) ?></p><?php endif; ?>

    <?php if ($course === ''): ?>
      <div class="my-empty">
        <strong>Nothing to submit yet</strong>
        <p>Workbooks belong to a qualification you are enrolled on. Email
          <a href="mailto:<?= e(brand('academy_email')) ?>"><?= e(brand('academy_email')) ?></a>
          if you were expecting to see yours.</p>
      </div>
    <?php else: ?>

      <div class="lb-head">
        <div>
          <h3 class="my-h"><?= e(learner_course_title($course)) ?></h3>
          <p class="lb-who"><strong><?= (int) $done ?></strong> of <?= count($slots) ?> submitted</p>
        </div>
        <?php if (count($mine) > 1): ?>
          <form method="GET" class="lb-pick">
            <select name="c" onchange="this.form.submit()" aria-label="Course">
              <?php foreach ($mine as $slug): ?>
                <option value="<?= e($slug) ?>" <?= $slug === $course ? 'selected' : '' ?>><?= e(learner_course_title($slug)) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
      </div>

      <div class="box-note">
        <strong>How it works</strong>
        <ol>
          <li>Download the workbook below. It opens in Word.</li>
          <li>Complete it and save it as a <strong>.docx</strong> (a PDF is also accepted).</li>
          <li>Upload it on this page. You can send a new version until your facilitator has assessed it.</li>
        </ol>
        <p>Your <a href="<?= e('logbook?c=' . rawurlencode($course)) ?>">logbook</a> is separate: you type it
          on the platform, print it, and sign it with your facilitator.</p>
      </div>

      <?php foreach (['workbook' => 'Workbooks', 'practical' => 'Practical workbooks'] as $kindKey => $heading): ?>
        <?php $rows = array_values(array_filter($slots, fn($s) => $s['kind'] === $kindKey)); ?>
        <?php if (!$rows) continue; ?>
        <h3 class="my-h"><?= e($heading) ?></h3>
        <div class="sub-list">
          <?php foreach ($rows as $s): ?>
            <?php
              $key    = $s['code'] . ':' . $s['kind'];
              $past   = $history[$key] ?? [];
              $latest = $past[0] ?? null;
              $st     = $poe[$key] ?? null;
              $locked = $st !== null && $st['status'] === 'assessed';
              $dl     = $material[$s['code']]['workbook']['open'] ?? null;
              $manual = $s['kind'] === 'practical' ? ($material[$s['code']]['guide']['open'] ?? null) : null;
            ?>
            <div class="sub-card">
              <div class="sub-head">
                <div>
                  <span class="my-mod-code"><?= e($s['code']) ?></span>
                  <strong><?= e($s['title']) ?></strong>
                </div>
                <?php if ($st): ?>
                  <span class="poe-mark <?= e($statusClass[$st['status']] ?? '') ?>"><?= e(poe_status_label((string) $st['status'])) ?></span>
                <?php else: ?>
                  <span class="poe-empty">Not submitted</span>
                <?php endif; ?>
              </div>

              <p class="sub-links">
                <?php if ($dl): ?><a href="<?= e($dl) ?>">Download the <?= $s['kind'] === 'practical' ? 'practical workbook' : 'workbook' ?></a><?php else: ?>
                  <span class="field-hint">The workbook has not been loaded yet. Ask your facilitator.</span><?php endif; ?>
                <?php if ($manual): ?> · <a href="<?= e($manual) ?>">Download the practical manual</a><?php endif; ?>
              </p>

              <?php if ($latest): ?>
                <p class="sub-last">Your latest: <a href="<?= e('poe-file?id=' . (int) $latest['id']) ?>"><?= e((string) $latest['original_name']) ?></a>
                  · <?= e(submission_when((string) $latest['submitted_at'])) ?>
                  <?= count($past) > 1 ? '· ' . count($past) . ' versions sent' : '' ?></p>
              <?php endif; ?>
              <?php if ($st && !empty($st['note']) && $st['status'] !== 'handed_in'): ?>
                <p class="sub-last">Facilitator's note: <?= e((string) $st['note']) ?></p>
              <?php endif; ?>

              <?php if ($locked): ?>
                <p class="field-hint">This has been assessed, so it is locked. If it needs another look, ask your facilitator to return it to you.</p>
              <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="sub-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="do" value="upload">
                  <input type="hidden" name="c" value="<?= e($course) ?>">
                  <input type="hidden" name="code" value="<?= e($s['code']) ?>">
                  <input type="hidden" name="kind" value="<?= e($s['kind']) ?>">
                  <input type="file" name="file" required aria-label="Your completed document for <?= e($s['code']) ?>"
                         accept=".docx,.doc,.pdf">
                  <button class="btn btn-primary" type="submit"><?= $latest ? 'Send a new version' : 'Submit' ?></button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>

  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
