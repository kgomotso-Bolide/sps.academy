<?php
declare(strict_types=1);

/* Recognition of prior learning — the assessor's page.
 *
 * Admins only: on these academies the assessor and the administrator are the
 * same person (Sibusiso, 28 Oct 2026), and a decision here changes what counts
 * towards somebody's qualification, so a trainer does not get it.
 *
 * Three things, top to bottom: the standard list the assessor approves once,
 * the learners on the course with where their certificate stands, and — with
 * ?u=<id> — one learner's hand-ins and the decision form. Everything a decision
 * does is lib/rpl.php.
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

$me = require_admin();

$courses = array_filter(
    learner_catalogue(),
    fn(array $c, string $slug): bool => rpl_default_route($slug) !== null,
    ARRAY_FILTER_USE_BOTH
);
$course = (string) ($_GET['course'] ?? $_POST['course'] ?? '');
if (!isset($courses[$course])) $course = (string) (array_key_first($courses) ?? '');
$route  = $course !== '' ? (string) rpl_default_route($course) : '';
$info   = $course !== '' ? rpl_route($course, $route) : null;
$userId = (int) ($_GET['u'] ?? $_POST['u'] ?? 0);

$notice = '';
$errors = [];

if (is_post() && $course !== '') {
    if (!csrf_valid()) {
        $errors[] = 'That form had expired — nothing was saved. Please try again.';
    } else {
        $do   = (string) ($_POST['do'] ?? '');
        $mods = (array) ($_POST['modules'] ?? []);
        $note = (string) ($_POST['note'] ?? '');
        $res = db_optional(fn() => match ($do) {
            'standard' => rpl_standard_set($course, $route, $mods, (int) $me['id']),
            'decide'   => rpl_decide($userId, $course, $route, $mods, $note, (int) $me['id']),
            'decline'  => rpl_decline($userId, $course, $note, (int) $me['id']),
            default    => [false, 'Unknown action.'],
        }, null);
        if ($res === null) {
            $errors[] = db_schema_notice();
        } else {
            [$ok, $msg] = $res;
            $ok ? $notice = $msg : $errors[] = $msg;
        }
        csrf_rotate();
    }
}

$modules  = $course !== '' ? curriculum_modules($course) : [];
$standard = $course !== '' ? (db_optional(fn() => rpl_standard($course, $route), []) ?: []) : [];
$stdMeta  = $course !== '' ? db_optional(fn() => rpl_standard_meta($course, $route)) : null;

$learners = $course !== ''
    ? (db_optional(fn() => db_all(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.employee_no
           FROM users u
           JOIN enrolments e ON e.user_id = u.id AND e.tenant_id = u.tenant_id
          WHERE u.tenant_id = ? AND e.course_slug = ? AND u.role = ?
          ORDER BY u.last_name, u.first_name',
        [tenant_id(), $course, 'learner']
      ), []) ?: [])
    : [];
$latest = $course !== '' ? (db_optional(fn() => rpl_latest_claims($course), []) ?: []) : [];
$counts = $course !== '' ? (db_optional(fn() => rpl_credit_counts($course), []) ?: []) : [];

$one = null;
foreach ($learners as $l) if ((int) $l['id'] === $userId) $one = $l;
$claims  = $one ? (db_optional(fn() => rpl_claims_for($userId, $course), []) ?: []) : [];
$credits = $one ? (db_optional(fn() => rpl_credits_for($userId, $course), []) ?: []) : [];
/* What the form starts ticked with: this learner's decision if there is one,
   otherwise the approved standard list. Never the unapproved suggestion. */
$ticked = $credits ? array_keys($credits) : $standard;

audit('rpl.admin_viewed', 'rpl_claims', $one ? $userId : null, $course);

$statusText = ['submitted' => 'Waiting for you', 'accepted' => 'Assessed', 'declined' => 'Sent back'];
$statusClass = ['submitted' => 'poe-handed_in', 'accepted' => 'poe-assessed', 'declined' => 'poe-returned'];

function rpl_qs(array $over = []): string
{
    $q = array_merge(['course' => $_GET['course'] ?? '', 'u' => $_GET['u'] ?? ''], $over);
    $q = array_filter($q, fn($v) => (string) $v !== '');
    return 'admin-rpl' . ($q ? '?' . http_build_query($q) : '');
}

/** The eleven tick boxes, used by both forms. */
function rpl_module_boxes(array $modules, array $ticked, array $mark = []): void
{
    echo '<div class="rpl-mods">';
    foreach ($modules as $mid => $m) {
        $id = 'rm-' . e((string) $mid);
        echo '<label class="rpl-mod" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="modules[]" value="'
           . e((string) $mid) . '"' . (in_array((string) $mid, $ticked, true) ? ' checked' : '') . '>'
           . '<span class="my-mod-code">' . e((string) $mid) . '</span> '
           . '<span>' . e((string) $m['title']) . ' <span class="adm-sub">' . (int) $m['credits'] . ' cr'
           . (in_array((string) $mid, $mark, true) ? ' · on the pathway page\'s list' : '') . '</span></span></label>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recognition of prior learning — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('admin', ['active' => 'rpl', 'name' => $me['first_name']]); ?>

<section class="section page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Assessor</span>
        <h2>Recognition of prior learning</h2>
      </div>
    </div>

    <div class="mat-intro">
      <p>Learners on the Project Manager start with the <?= e((string) ($info['title'] ?? 'Google certificate')) ?>
        and hand it in on their own page. Here you record which knowledge modules it is
        recognised for. Recognised modules count as complete for the learner, and they study
        the rest. Nothing is recognised until you save a decision.</p>
    </div>

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>
    <?php if ($notice !== ''): ?><p class="adm-notice" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php foreach ($errors as $er): ?><p class="form-err" role="alert"><?= e($er) ?></p><?php endforeach; ?>

    <?php if (!$courses || $info === null): ?>
      <div class="adm-empty"><strong>No course on this academy has a certificate route.</strong></div>
    <?php elseif ($one): ?>

      <p><a href="<?= e(rpl_qs(['u' => ''])) ?>">← All learners</a></p>
      <h3 class="my-h"><?= e(trim($one['first_name'] . ' ' . $one['last_name'])) ?></h3>
      <p class="adm-sub"><?= e((string) $one['email']) ?><?= $one['employee_no'] ? ' · Employee no ' . e((string) $one['employee_no']) : '' ?></p>

      <h3 class="my-h">Handed in</h3>
      <?php if (!$claims): ?>
        <p>Nothing handed in yet. You can still record a decision below — for example from a
          certificate the learner showed you on paper. Say so in the note.</p>
      <?php else: ?>
        <div class="adm-scroll">
        <table class="adm-table">
          <thead><tr><th>Handed in</th><th>Certificate</th><th>Status</th><th>Your note</th></tr></thead>
          <tbody>
            <?php foreach ($claims as $c): ?>
              <tr>
                <td><?= e(rpl_when((string) $c['submitted_at'])) ?></td>
                <td>
                  <?php if (!empty($c['verify_url'])): ?><a href="<?= e((string) $c['verify_url']) ?>" target="_blank" rel="noopener noreferrer">Check on Coursera ↗</a><br><?php endif; ?>
                  <?php if (!empty($c['disk_name'])): ?><a href="<?= e('rpl-file?id=' . (int) $c['id']) ?>"><?= e((string) $c['original_name']) ?></a><?php endif; ?>
                </td>
                <td><span class="poe-mark <?= e($statusClass[$c['status']] ?? '') ?>"><?= e($statusText[$c['status']] ?? (string) $c['status']) ?></span>
                  <?php if ($c['reviewed_at']): ?><span class="adm-sub"><?= e(rpl_when((string) $c['reviewed_at'])) ?></span><?php endif; ?></td>
                <td><?= e((string) ($c['review_note'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <p class="field-hint">Open the Coursera link to confirm the certificate is real and in this learner's name before recognising anything.</p>
      <?php endif; ?>

      <h3 class="my-h">Decision</h3>
      <?php if (!$credits && !$standard): ?>
        <p class="form-err" role="status">No standard list has been approved yet, so nothing is ticked. Tick the modules for this learner, or approve the standard list first.</p>
      <?php elseif (!$credits): ?>
        <p class="field-hint">Ticked from the approved standard list. Change it for this learner if their certificate warrants it.</p>
      <?php endif; ?>
      <form method="POST" class="form rpl-form">
        <?= csrf_field() ?>
        <input type="hidden" name="course" value="<?= e($course) ?>">
        <input type="hidden" name="u" value="<?= (int) $userId ?>">
        <?php rpl_module_boxes($modules, $ticked); ?>
        <div class="field">
          <label for="r-note">Note</label>
          <input id="r-note" type="text" name="note" maxlength="500"
                 placeholder="What you checked, or why this learner differs from the standard list">
        </div>
        <div class="rpl-act">
          <button class="btn btn-primary" type="submit" name="do" value="decide">Save decision</button>
          <?php if (($claims[0]['status'] ?? '') === 'submitted'): ?>
            <button class="btn btn-ghost" type="submit" name="do" value="decline"
                    title="Sends the hand-in back with your note. Nothing recognised is changed.">Send the certificate back</button>
          <?php endif; ?>
        </div>
      </form>

    <?php else: ?>

      <?php if (count($courses) > 1): ?>
        <div class="adm-tabs">
          <?php foreach ($courses as $slug => $c): ?>
            <a class="adm-tab<?= $slug === $course ? ' is-on' : '' ?>" href="<?= e(rpl_qs(['course' => $slug, 'u' => ''])) ?>"><?= e((string) ($c['title'] ?? $slug)) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h3 class="my-h">Learners</h3>
      <?php if (!$learners): ?>
        <div class="adm-empty"><strong>Nobody is enrolled on this course yet.</strong></div>
      <?php else: ?>
        <div class="adm-scroll">
        <table class="adm-table">
          <thead><tr><th>Learner</th><th>Certificate</th><th>Recognised</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($learners as $l): ?>
              <?php $c = $latest[(int) $l['id']] ?? null; $n = $counts[(int) $l['id']] ?? 0; ?>
              <tr>
                <td><strong><?= e(trim($l['first_name'] . ' ' . $l['last_name'])) ?></strong>
                  <span class="adm-sub"><?= e((string) $l['email']) ?></span></td>
                <td><?php if ($c): ?>
                    <span class="poe-mark <?= e($statusClass[$c['status']] ?? '') ?>"><?= e($statusText[$c['status']] ?? (string) $c['status']) ?></span>
                    <span class="adm-sub"><?= e(rpl_when((string) $c['submitted_at'])) ?></span>
                  <?php else: ?><span class="poe-empty">Not handed in</span><?php endif; ?></td>
                <td><?= $n ? (int) $n . ' of ' . count($modules) . ' modules' : '—' ?></td>
                <td><a class="btn btn-ghost" href="<?= e(rpl_qs(['u' => (int) $l['id']])) ?>"><?= ($c && $c['status'] === 'submitted') ? 'Assess' : 'Open' ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>

      <h3 class="my-h">Standard list</h3>
      <?php if ($standard): ?>
        <p class="field-hint">Approved<?= $stdMeta ? ' ' . e(rpl_when((string) $stdMeta['set_at'])) . ' by ' . e(trim($stdMeta['first_name'] . ' ' . $stdMeta['last_name'])) : '' ?>.
          Every new decision starts from it. Changing it does not change learners already decided.</p>
      <?php else: ?>
        <p class="form-err" role="status">Not approved yet. The boxes are ticked with the pathway page's own reading of the
          Google syllabus — a starting point for you, not a decision. Nothing is applied to any learner until you save it.</p>
      <?php endif; ?>
      <form method="POST" class="form rpl-form">
        <?= csrf_field() ?>
        <input type="hidden" name="course" value="<?= e($course) ?>">
        <?php rpl_module_boxes($modules, $standard ?: $info['suggested'], $info['suggested']); ?>
        <button class="btn btn-primary" type="submit" name="do" value="standard">Save as the standard list</button>
      </form>

    <?php endif; ?>

  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
