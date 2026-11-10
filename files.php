<?php
declare(strict_types=1);

/* My files: every guide and workbook for a learner's courses, on one page.
 *
 * Added 10 Nov 2026 on Kgomotso's instruction: "Needs to have a simple way to
 * access files, not too many processes." Until now a file was reached through
 * My learning -> a module -> the material panel on that module, one module at a
 * time. This lists every module of every course the learner is on, with each
 * file one click away.
 *
 * Nothing about access changes. Every button is a materials.php?open= token,
 * the same as on the module page: the real Drive link or file path never
 * reaches the page, and materials.php checks enrolment again on every open and
 * logs it. A course the learner is not on is simply not listed.
 *
 * Hovering a module (or tapping its "i" on a phone) shows what it covers, from
 * the curriculum files: see module-info.js.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/materials.php';
require __DIR__ . '/lib/material_files.php';
require __DIR__ . '/lib/submissions.php';
require __DIR__ . '/lib/chrome.php';

$me = require_user();

$enrolments = db_optional(fn() => learner_enrolments((int) $me['id']), []) ?: [];
$only = (string) ($_GET['c'] ?? '');

/* Staff are not enrolled; they see the material from Admin > Material. */
$courses = [];
foreach ($enrolments as $en) {
    $slug = (string) $en['course_slug'];
    if (!learner_course_tracked($slug)) continue;
    $courses[$slug] = (string) $en['course_title'];
}
if ($only !== '' && isset($courses[$only])) {
    $courses = [$only => $courses[$only]];
}

audit('files.viewed', 'users', (int) $me['id'], implode(', ', array_keys($courses)) ?: 'no courses');

/** The label a learner sees on each file button, per course. */
function files_kind_label(string $slug, string $kind, bool $practical): string
{
    if ($practical) return $kind === 'guide' ? 'Practical manual' : 'Practical workbook';
    if ($kind === 'workbook' && $slug === 'new-venture-creation') return 'Workplace Guide';
    return ['guide' => 'Learner Guide', 'workbook' => 'Workbook', 'video' => 'Video'][$kind] ?? ucfirst($kind);
}

$fileIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
          . 'stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 '
          . '2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My files — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'files', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<section class="section-dark page-top">
  <div class="wrap">
    <div class="sec-head sec-head-wide">
      <span class="eyebrow"><?= e(brand('academy')) ?></span>
      <h2 class="lede-h">My files</h2>
      <p>Every guide and workbook for your courses, in one place. Click a file to open or
        download it. Hover over a module to see what it covers.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>

    <?php if (!$courses): ?>
      <div class="my-empty">
        <strong>No files yet.</strong>
        <p><?= $me['role'] === 'learner'
              ? 'You are not on a course with files yet. <a href="my#add-course">Choose a course</a> and its files appear here.'
              : 'Staff see every course&rsquo;s files under <a href="admin-materials">Administration &rsaquo; Material</a>.' ?></p>
      </div>
    <?php endif; ?>

    <?php foreach ($courses as $slug => $title): ?>
      <?php
        $slots = materials_slots_for_course($slug);
        $rows = [];
        foreach (curriculum_modules($slug) as $id => $m) {
            $rows[] = ['id' => (string) $id, 'title' => (string) $m['title'], 'practical' => false];
        }
        foreach (submission_practicals($slug) as $id => $t) {
            $rows[] = ['id' => (string) $id, 'title' => $t, 'practical' => true];
        }
        $kinds = ['guide', 'workbook', 'video'];
        $haveAny = $slots !== [];
      ?>
      <div class="fl-course" data-course="<?= e($slug) ?>">
        <div class="fl-head">
          <h3><?= e($title) ?></h3>
          <?php if (count($courses) > 1 || $only === ''): ?>
            <a class="fl-link" href="<?= e('course?c=' . rawurlencode($slug)) ?>">About this course →</a>
          <?php endif; ?>
        </div>
        <?php if (!$haveAny): ?>
          <p class="fl-none">The files for this course have not been uploaded yet. They will appear
            here as soon as the academy adds them.</p>
        <?php endif; ?>
        <ul class="fl-list">
          <?php foreach ($rows as $r): ?>
            <?php $have = $slots[$r['id']] ?? []; ?>
            <li class="fl-row" data-mod="<?= e($r['id']) ?>" data-title="<?= e($r['title']) ?>"<?= $r['practical'] ? ' data-practical="1"' : '' ?>>
              <a class="fl-mod" href="<?= e($r['practical'] ? 'submit?c=' . rawurlencode($slug) : 'module?m=' . rawurlencode($r['id']) . ($slug !== 'project-management' ? '&c=' . rawurlencode($slug) : '')) ?>">
                <span class="fl-code"><?= e($r['id']) ?></span>
                <span class="fl-title"><?= e($r['title']) ?></span>
              </a>
              <span class="fl-files">
                <?php $n = 0; foreach ($kinds as $k): if (empty($have[$k])) continue; $n++; ?>
                  <a class="fl-file" href="<?= e((string) $have[$k]['open']) ?>" target="_blank" rel="noopener"
                     title="Open the <?= e(files_kind_label($slug, $k, $r['practical'])) ?>"><?= $fileIcon ?><?= e(files_kind_label($slug, $k, $r['practical'])) ?></a>
                <?php endforeach; ?>
                <?php if ($n === 0): ?><span class="fl-soon">Not uploaded yet</span><?php endif; ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('pm-modules.js')) ?>"></script>
<script src="<?= e(asset('po-modules.js')) ?>"></script>
<script src="<?= e(asset('nvc-modules.js')) ?>"></script>
<script src="<?= e(asset('module-info.js')) ?>"></script>
</body></html>
