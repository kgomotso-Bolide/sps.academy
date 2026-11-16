<?php
declare(strict_types=1);

/* The study tutor: its own page (GET) and its answers (POST, JSON).
 *
 * SHARED: synced to every academy; on where lib/brand.php sets 'tutor'.
 * The engine and the reasoning behind it are in lib/tutor.php. tutor.js draws
 * the conversation, both here and as the "Ask your tutor" panel on the module
 * pages.
 *
 *   POST q, course, module?, topic?, _token  ->  {ok, blocks, chips, focus?}
 *
 * Signed in, CSRF-checked, and only on a course the person is enrolled on
 * (staff may ask on any course, to see what learners see). The question is
 * not stored. See lib/tutor.php, PRIVACY.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/rpl.php';
require __DIR__ . '/lib/tutor.php';
require __DIR__ . '/lib/chrome.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $j = static function (array $d, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    };
    app_session_start();
    $me = current_user();
    if ($me === null)            $j(['ok' => false, 'error' => 'signin', 'message' => 'Please sign in again.'], 401);
    if (!brand_has('tutor'))     $j(['ok' => false, 'error' => 'off', 'message' => 'The tutor is not switched on here.'], 404);
    if (!csrf_valid())           $j(['ok' => false, 'error' => 'token', 'message' => 'Please refresh the page and ask again.'], 403);
    if (!tutor_rate_ok())        $j(['ok' => false, 'error' => 'rate', 'message' => "That's a lot of questions for one hour. Take a break and come back."], 429);

    $q      = trim((string) ($_POST['q'] ?? ''));
    $course = (string) ($_POST['course'] ?? '');
    $module = strtoupper((string) ($_POST['module'] ?? ''));
    $topic  = strtoupper((string) ($_POST['topic'] ?? ''));
    if ($q === '' || mb_strlen($q) > TUTOR_MAX_Q) $j(['ok' => false, 'error' => 'q', 'message' => 'Ask a question of up to ' . TUTOR_MAX_Q . ' characters.'], 400);
    if (!learner_valid_course_slug($course) || !isset(curriculum_files()[$course])) $j(['ok' => false, 'error' => 'course', 'message' => 'Choose one of your courses.'], 400);
    $mods = curriculum_modules($course);
    if ($module !== '' && !isset($mods[$module])) $module = '';
    if ($topic !== '' && ($module === '' || !isset($mods[$module]['topics'][$topic]))) $topic = '';

    $staff = in_array($me['role'], ['admin', 'trainer'], true);
    if (!$staff && !db_optional(fn() => learner_is_enrolled((int) $me['id'], $course), false)) {
        $j(['ok' => false, 'error' => 'enrol', 'message' => "You're not on this course yet. Add it on Home first."], 403);
    }

    /* The conversation so far, as the browser holds it: the last question (for
       "tell me more"), passages already shown, quiz points already asked. */
    $ints = static fn(string $k): array => array_slice(array_values(array_filter(array_map('intval',
        explode(',', (string) ($_POST[$k] ?? ''))), fn($n) => $n >= 0)), 0, 200);
    $r = tutor_answer(['q' => $q, 'course' => $course, 'module' => $module, 'topic' => $topic,
                       'uid' => (int) $me['id'], 'first' => (string) $me['first_name'],
                       'lastq' => mb_substr(trim((string) ($_POST['lastq'] ?? '')), 0, TUTOR_MAX_Q),
                       'seen' => $ints('seen'), 'avoid' => $ints('avoid')]);
    /* What was asked about, never the words. */
    audit('tutor.asked', 'users', (int) $me['id'], $r['intent'] . ' · ' . $course . ($module !== '' ? ' ' . $module : ''));
    $j(['ok' => true] + $r);
}

/* ---- The page ----------------------------------------------------------- */

$me = require_user();
if (!brand_has('tutor')) { header('Location: ' . (brand_has('app_shell') ? 'dashboard' : 'my'), true, 303); exit; }

$staff = in_array($me['role'], ['admin', 'trainer'], true);
$courses = [];
foreach ((db_optional(fn() => learner_enrolments((int) $me['id']), []) ?: []) as $en) {
    $s = (string) $en['course_slug'];
    if (isset(curriculum_files()[$s])) $courses[$s] = learner_course_title($s);
}
if ($staff) foreach (array_keys(curriculum_files()) as $s) $courses[$s] = $courses[$s] ?? learner_course_title($s);

$course = (string) ($_GET['c'] ?? '');
if (!isset($courses[$course])) $course = (string) (array_key_first($courses) ?? '');
$module = strtoupper((string) ($_GET['m'] ?? ''));
$mods = $course !== '' ? curriculum_modules($course) : [];
if (!isset($mods[$module])) $module = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'tutor', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<main class="dash tutor-page">
  <header class="dash-title">
    <h1>Your study <span>tutor</span></h1>
    <p>Ask about anything in your course. Answers come from your course material, with the topic they came from.</p>
  </header>

  <?php if (!$courses): ?>
    <p class="dash-muted">You're not on a course yet. <a href="<?= brand_has('app_shell') ? 'dashboard' : 'my' ?>">Choose one first</a>, then come back.</p>
  <?php else: ?>
    <form class="tutor-scope" method="get" action="tutor">
      <label>Course
        <select name="c" onchange="this.form.m.value='';this.form.submit()">
          <?php foreach ($courses as $s => $t): ?><option value="<?= e($s) ?>"<?= $s === $course ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Module
        <select name="m" onchange="this.form.submit()">
          <option value="">The whole course</option>
          <?php foreach ($mods as $id => $m): ?><option value="<?= e($id) ?>"<?= $id === $module ? ' selected' : '' ?>><?= e($id . ' · ' . $m['title']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <noscript><button class="btn btn-primary btn-sm">Go</button></noscript>
    </form>

    <div id="tutor" class="tutor-inline"
         data-course="<?= e($course) ?>" data-module="<?= e($module) ?>"
         data-token="<?= e(csrf_token()) ?>" data-first="<?= e((string) $me['first_name']) ?>"></div>

    <p class="tutor-note">The tutor works from your course material only, and won't give answers to topic checks, workbooks or the final assessment. Your questions are not saved.</p>
  <?php endif; ?>
</main>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('tutor.js')) ?>"></script>
</body></html>
