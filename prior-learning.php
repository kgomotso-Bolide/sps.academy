<?php
declare(strict_types=1);

/* Stage 1 of the Project Manager: the Google certificate, and what it was
 * recognised for.
 *
 * The learner does the Google Project Management Certificate on Coursera, then
 * hands it in here — the Coursera link, the PDF, or both. The assessor records
 * which knowledge modules it is recognised for, and this page shows that
 * decision. What happens to a hand-in, and who decides, is lib/rpl.php.
 *
 * Not called rpl.php: rpl.html is an old page kept as a redirect because links
 * to it were emailed, and "rpl" would then be two different files.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/rpl.php';
require __DIR__ . '/lib/chrome.php';

$me = require_user();

$notice = '';
$error  = '';

$enrolments = db_optional(fn() => learner_enrolments((int) $me['id']), []) ?: [];
$mine = array_values(array_filter(
    array_map(fn($e) => (string) $e['course_slug'], $enrolments),
    fn($s) => rpl_default_route($s) !== null
));

$course = (string) ($_GET['c'] ?? $_POST['c'] ?? '');
if ($course === '' || !in_array($course, $mine, true)) $course = $mine[0] ?? '';
$route  = $course !== '' ? (string) rpl_default_route($course) : '';
$info   = $course !== '' ? rpl_route($course, $route) : null;

if ($course !== '' && is_post()) {
    if (!csrf_valid()) {
        $error = 'This page had been open a while and the form expired. Nothing was handed in, so please try again.';
    } else {
        $res = db_optional(fn() => rpl_claim_store(
            (int) $me['id'], $course, $route,
            (string) ($_POST['verify_url'] ?? ''), $_FILES['file'] ?? []
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

$claims  = $course !== '' ? (db_optional(fn() => rpl_claims_for((int) $me['id'], $course), []) ?: []) : [];
$latest  = $claims[0] ?? null;
$credits = $course !== '' ? (db_optional(fn() => rpl_credits_for((int) $me['id'], $course), []) ?: []) : [];
$modules = $course !== '' ? curriculum_modules($course) : [];

audit('rpl.viewed', 'rpl_claims', (int) $me['id'], $course);

$statusText = [
    'submitted' => 'Handed in — waiting for the assessor',
    'accepted'  => 'Assessed',
    'declined'  => 'Sent back to you',
];
$statusClass = ['submitted' => 'poe-handed_in', 'accepted' => 'poe-assessed', 'declined' => 'poe-returned'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Google certificate &amp; RPL — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'prior-learning', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<section class="section-dark page-top">
  <div class="wrap">
    <div class="sec-head sec-head-wide">
      <span class="eyebrow">Stage 1</span>
      <h2 class="lede-h">Google certificate &amp; RPL</h2>
      <p>Start with the Google Project Management Certificate. When you have it, hand it in here.
        The academy's assessor records which of the knowledge modules it covers — those are
        recognised through RPL, and you study only the rest.</p>
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

    <?php if ($course === '' || $info === null): ?>
      <div class="my-empty">
        <strong>Nothing to hand in here</strong>
        <p>This page is for learners on the Project Manager, who start with the Google
          certificate. Email
          <a href="mailto:<?= e(brand('academy_email')) ?>"><?= e(brand('academy_email')) ?></a>
          if you expected to see it.</p>
      </div>
    <?php else: ?>

      <div class="lb-head">
        <div>
          <h3 class="my-h"><?= e(learner_course_title($course)) ?></h3>
          <p class="lb-who"><strong><?= count($credits) ?></strong> of <?= count($modules) ?> knowledge modules recognised</p>
        </div>
        <?php if ($latest): ?>
          <span class="poe-mark <?= e($statusClass[$latest['status']] ?? '') ?>"><?= e($statusText[$latest['status']] ?? (string) $latest['status']) ?></span>
        <?php endif; ?>
      </div>

      <div class="box-note">
        <strong>How it works</strong>
        <ol>
          <li>Do the <a href="<?= e($info['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($info['title']) ?></a>
            on <?= e($info['provider']) ?>.</li>
          <li>When Coursera awards it, copy the certificate's share link, download the PDF, or both, and hand them in below.</li>
          <li>The assessor checks it and records which knowledge modules it covers. Those show as
            <strong>Recognised</strong> on your learning page, and you carry on with the others.</li>
        </ol>
        <p>Nothing is recognised automatically — a person looks at every certificate. The practical
          and workplace modules are not covered by the Google certificate, and everyone sits the
          final external assessment. <a href="pm-pathway">How the Google certificate fits</a>.</p>
      </div>

      <?php if ($latest && $latest['status'] === 'declined' && !empty($latest['review_note'])): ?>
        <p class="form-err" role="status"><strong>The assessor sent this back:</strong> <?= e((string) $latest['review_note']) ?></p>
      <?php endif; ?>

      <?php if ($credits): ?>
        <h3 class="my-h">Recognised modules</h3>
        <div class="sub-list">
          <?php foreach ($modules as $mid => $m): ?>
            <?php if (!isset($credits[$mid])) continue; ?>
            <div class="sub-card rpl-card">
              <div class="sub-head">
                <div>
                  <span class="my-mod-code"><?= e((string) $mid) ?></span>
                  <strong><?= e((string) $m['title']) ?></strong>
                </div>
                <span class="poe-mark poe-assessed">Recognised</span>
              </div>
              <p class="sub-last">Recorded <?= e(rpl_when((string) $credits[$mid]['decided_at'])) ?> · <?= (int) $m['credits'] ?> credits</p>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="my-mod-note">Every other module is yours to study as normal, from
          <a href="my">My learning</a>.</p>
      <?php endif; ?>

      <?php if (!$latest || $latest['status'] !== 'accepted'): ?>
        <h3 class="my-h"><?= $latest ? 'Hand in again' : 'Hand in your certificate' ?></h3>
        <form method="POST" enctype="multipart/form-data" class="form rpl-form">
          <?= csrf_field() ?>
          <input type="hidden" name="c" value="<?= e($course) ?>">
          <div class="field">
            <label for="r-url">Coursera certificate link</label>
            <input id="r-url" type="url" name="verify_url" maxlength="500"
                   placeholder="https://coursera.org/verify/professional-cert/…">
            <p class="field-hint">On Coursera, open the certificate and use its Share or Copy link button.</p>
          </div>
          <div class="field">
            <label for="r-file">Certificate PDF</label>
            <input id="r-file" type="file" name="file" accept=".pdf,application/pdf">
            <p class="field-hint">Optional if you gave the link. Up to <?= (int) (RPL_MAX_BYTES / 1048576) ?> MB.</p>
          </div>
          <button class="btn btn-primary" type="submit">Hand it in</button>
        </form>
      <?php endif; ?>

      <?php if ($claims): ?>
        <h3 class="my-h">What you have handed in</h3>
        <div class="sub-list">
          <?php foreach ($claims as $c): ?>
            <div class="sub-card">
              <p class="sub-last">
                <?= e(rpl_when((string) $c['submitted_at'])) ?> ·
                <?= e($statusText[$c['status']] ?? (string) $c['status']) ?>
                <?php if (!empty($c['verify_url'])): ?> · <a href="<?= e((string) $c['verify_url']) ?>" target="_blank" rel="noopener noreferrer">Certificate link</a><?php endif; ?>
                <?php if (!empty($c['disk_name'])): ?> · <a href="<?= e('rpl-file?id=' . (int) $c['id']) ?>"><?= e((string) $c['original_name']) ?></a><?php endif; ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
