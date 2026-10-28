<?php
declare(strict_types=1);

/* Training evidence, for HR to hand to the B-BBEE practitioner.
 *
 * WHAT THIS IS FOR
 *
 * A company's Skills Development element is scored by its verification agency
 * from evidence: who was on a programme, and an attendance register to prove it.
 * The academy holds that evidence already, spread over enrolments, registers,
 * quizzes and files. This page gathers it for one course over one period and
 * exports it, so HR can attach it to the programme in the BEE tool.
 *
 * WHAT THIS IS NOT
 *
 * A B-BBEE calculation. The academy does not calculate B-BBEE — see the note at
 * the top of scorecard.php — and nothing here is points, spend or a Learning
 * Programme Matrix category. Those belong to the BEE tool and the practitioner.
 *
 * NO "COMPLETED" COLUMN, DELIBERATELY. enrolments.completed_at exists in the
 * schema but nothing sets it, so the academy has never recorded that anyone
 * finished a course. Printing "Completed" would be inventing it. The export
 * reports the evidence — classes attended, quizzes passed, files assessed — and
 * leaves the conclusion to the person who is qualified to draw it.
 *
 * WHAT IS LEFT OUT, DELIBERATELY. Email addresses, quiz answers and anything
 * demographic. The BEE tool matches people on employee number and holds race,
 * gender and disability itself; under POPIA those are special personal
 * information and the academy has never needed them. A file that leaves this
 * system carries only what the next step needs.
 *
 * WHO. Administrators only — on this system HR are the administrators — on
 * Sibusiso's instruction of 27 Oct 2026. Not trainers. Every view and every
 * export is audited, as on the other administration pages.
 *
 * THE EXPORT IS A CONTRACT. The BEE tool reads the learner CSV by its column
 * headings ("Employee no" is the one it matches on). Rename a heading here and
 * the import there stops linking people. See academyEvidenceImport() in the
 * beetool repository before changing one.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/quiz.php';
require __DIR__ . '/lib/classes.php';
require __DIR__ . '/lib/logbook.php';
require __DIR__ . '/lib/rpl.php';
require __DIR__ . '/lib/chrome.php';

$me = require_admin();

$courses = learner_catalogue();

$course = (string) ($_GET['course'] ?? '');
if (!isset($courses[$course])) $course = (string) (array_key_first($courses) ?? '');

/* The period. Defaults to the twelve months to today, because a company's
   B-BBEE measurement year is its own financial year and this page cannot know
   it — HR sets the dates to match. Bad input falls back rather than erroring. */
$to   = (string) ($_GET['to'] ?? '');
$from = (string) ($_GET['from'] ?? '');
if (!class_valid_date($to))   $to   = gmdate('Y-m-d');
if (!class_valid_date($from)) $from = gmdate('Y-m-d', strtotime($to . ' -1 year +1 day'));
if ($from > $to) [$from, $to] = [$to, $from];
$toEnd = $to . ' 23:59:59';

/**
 * Everything the page and both exports show, in one place, so the screen and
 * the files cannot disagree.
 *
 * @return array{learners: array, classes: array, register: array, quizCount: int}
 */
function evidence_gather(string $course, string $from, string $to, string $toEnd): array
{
    $t = tenant_id();

    $learners = db_all(
        'SELECT u.id, u.first_name, u.last_name, u.employee_no, u.department,
                e.enrolled_at, e.status AS enrol_status
           FROM enrolments e
           JOIN users u ON u.id = e.user_id AND u.tenant_id = e.tenant_id
          WHERE e.tenant_id = ? AND e.course_slug = ? AND u.role = ? AND e.enrolled_at <= ?
          ORDER BY u.last_name, u.first_name',
        [$t, $course, 'learner', $toEnd]
    );

    /* Classes count once a register has been marked — that is what sets 'held'
       (lib/classes.php). A scheduled class nobody marked proves nothing. */
    $classes = db_all(
        'SELECT c.id, c.title, c.venue, c.held_on, c.starts_at, c.ends_at,
                f.first_name AS f_first, f.last_name AS f_last
           FROM classes c
           LEFT JOIN users f ON f.id = c.facilitator_id AND f.tenant_id = c.tenant_id
          WHERE c.tenant_id = ? AND c.course_slug = ? AND c.status = ?
            AND c.held_on >= ? AND c.held_on <= ?
          ORDER BY c.held_on, c.starts_at',
        [$t, $course, 'held', $from, $to]
    );

    $register = [];   // [classId][userId] => status
    if ($classes) {
        $ids = array_map(fn($c) => (int) $c['id'], $classes);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        foreach (db_all(
            'SELECT class_id, user_id, status FROM class_attendance
              WHERE tenant_id = ? AND class_id IN (' . $in . ')',
            array_merge([$t], $ids)
        ) as $r) $register[(int) $r['class_id']][(int) $r['user_id']] = (string) $r['status'];
    }

    /* Quizzes: only published ones count towards the total, and a learner's
       best attempt up to the end of the period decides passed or not, against
       the effective pass mark (quiz_pass_pct — NULL means the academy default). */
    $quizzes = db_all(
        'SELECT id, pass_pct FROM quizzes WHERE tenant_id = ? AND course_slug = ? AND published = 1',
        [$t, $course]
    );
    $passMark = [];
    foreach ($quizzes as $q) $passMark[(int) $q['id']] = quiz_pass_pct($q);

    $best = [];   // [userId][quizId] => best pct
    if ($quizzes && $learners) {
        $qin = implode(',', array_fill(0, count($passMark), '?'));
        foreach (db_all(
            'SELECT user_id, quiz_id, score_count, question_count FROM quiz_attempts
              WHERE tenant_id = ? AND quiz_id IN (' . $qin . ') AND submitted_at <= ?',
            array_merge([$t], array_keys($passMark), [$toEnd])
        ) as $a) {
            $qc  = (int) $a['question_count'];
            $pct = $qc > 0 ? $a['score_count'] / $qc * 100 : 0;
            $u = (int) $a['user_id']; $q = (int) $a['quiz_id'];
            if (!isset($best[$u][$q]) || $pct > $best[$u][$q]) $best[$u][$q] = $pct;
        }
    }

    $files = [];  // [userId] => ['in' => n, 'assessed' => n]
    foreach (db_all(
        'SELECT user_id, status, COUNT(*) AS n FROM poe_submissions
          WHERE tenant_id = ? AND course_slug = ? AND updated_at <= ?
          GROUP BY user_id, status',
        [$t, $course, $toEnd]
    ) as $r) {
        $u = (int) $r['user_id'];
        $files[$u] ??= ['in' => 0, 'assessed' => 0];
        if ($r['status'] === 'assessed') $files[$u]['assessed'] += (int) $r['n'];
        if ($r['status'] !== 'returned') $files[$u]['in'] += (int) $r['n'];
    }

    /* Knowledge modules recognised through RPL by the end of the period (28 Oct
       2026). NULL — an empty cell — on a course with no RPL route, which is a
       different fact from "none recognised". */
    $rpl = [];
    if (rpl_default_route($course) !== null) {
        foreach (db_optional(fn() => db_all(
            'SELECT user_id, COUNT(*) AS n FROM rpl_credits
              WHERE tenant_id = ? AND course_slug = ? AND decided_at <= ? GROUP BY user_id',
            [$t, $course, $toEnd]
        ), []) ?: [] as $r) {
            $rpl[(int) $r['user_id']] = (int) $r['n'];
        }
    }
    $hasRpl = rpl_default_route($course) !== null;

    foreach ($learners as &$l) {
        $u = (int) $l['id'];
        $attended = 0;
        foreach ($classes as $c) {
            $st = $register[(int) $c['id']][$u] ?? null;
            if ($st === 'present' || $st === 'late') $attended++;
        }
        $passed = 0;
        foreach ($passMark as $qid => $bar) {
            if (isset($best[$u][$qid]) && (int) round($best[$u][$qid]) >= $bar) $passed++;
        }
        $l['attended']       = $attended;
        $l['quizzes_passed'] = $passed;
        $l['files_in']       = $files[$u]['in'] ?? 0;
        $l['files_assessed'] = $files[$u]['assessed'] ?? 0;
        $l['rpl']            = $hasRpl ? ($rpl[$u] ?? 0) : null;
    }
    unset($l);

    return ['learners' => $learners, 'classes' => $classes, 'register' => $register,
            'quizCount' => count($passMark)];
}

$data = $course !== ''
    ? db_optional(fn() => evidence_gather($course, $from, $to, $toEnd), null)
    : null;
$data ??= ['learners' => [], 'classes' => [], 'register' => [], 'quizCount' => 0];

$courseTitle = (string) ($courses[$course]['title'] ?? $course);
$period      = $from . ' to ' . $to;
$noEmpNo     = count(array_filter($data['learners'], fn($l) => trim((string) $l['employee_no']) === ''));

$export = (string) ($_GET['export'] ?? '');
if ($export === 'learners' || $export === 'register') {
    $rowsOut = $export === 'learners' ? count($data['learners'])
                                      : count($data['learners']) * count($data['classes']);
    audit('evidence.exported', 'enrolments', null,
          $export . ' — ' . $course . ' — ' . $period . ' — ' . $rowsOut . ' rows');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="evidence-' . $export . '-' . $course . '-' . $from . '-to-' . $to . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    // Explicit empty $escape — see the note in admin.php.
    $put = static function ($h, array $row): void { fputcsv($h, $row, ',', '"', ''); };

    if ($export === 'learners') {
        $put($out, ['Employee no', 'First name', 'Last name', 'Department', 'Course', 'Period',
                    'Enrolled on', 'Enrolment status', 'Classes attended', 'Classes held',
                    'Quizzes passed', 'Quizzes published', 'Files handed in', 'Files assessed',
                    'Modules recognised (RPL)']);
        foreach ($data['learners'] as $l) {
            $put($out, [(string) $l['employee_no'], $l['first_name'], $l['last_name'],
                        (string) $l['department'], $courseTitle, $period,
                        substr((string) $l['enrolled_at'], 0, 10), $l['enrol_status'],
                        $l['attended'], count($data['classes']),
                        $l['quizzes_passed'], $data['quizCount'],
                        $l['files_in'], $l['files_assessed'],
                        $l['rpl'] === null ? '' : $l['rpl']]);
        }
    } else {
        /* One row per learner per class. "Not marked" is written out, never
           left blank or turned into "absent" — the register's own rule. */
        $put($out, ['Date', 'Time', 'Class', 'Venue', 'Facilitator', 'Employee no',
                    'First name', 'Last name', 'Attendance']);
        foreach ($data['classes'] as $c) {
            $fac = trim((string) $c['f_first'] . ' ' . (string) $c['f_last']);
            foreach ($data['learners'] as $l) {
                $st = $data['register'][(int) $c['id']][(int) $l['id']] ?? null;
                $put($out, [$c['held_on'], trim((string) $c['starts_at'] . '–' . (string) $c['ends_at'], '–'),
                            $c['title'], (string) $c['venue'], $fac, (string) $l['employee_no'],
                            $l['first_name'], $l['last_name'],
                            $st === null ? 'Not marked' : class_status_label($st)]);
            }
        }
    }
    fclose($out);
    exit;
}

audit('evidence.viewed', 'enrolments', null,
      $course . ' — ' . $period . ' — ' . count($data['learners']) . ' learner(s)');

function evidence_qs(array $over = []): string
{
    global $course, $from, $to;
    $q = array_merge(['course' => $course, 'from' => $from, 'to' => $to], $over);
    return 'admin-evidence?' . http_build_query(array_filter($q, fn($v) => (string) $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Training evidence — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('admin', ['active' => 'evidence', 'name' => $me['first_name']]); ?>

<section class="section page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Academy administration</span>
        <h2>Training evidence</h2>
      </div>
    </div>

    <div class="mat-intro">
      <p>Who was on a course over a period, and the evidence the academy holds for each of them:
         classes attended, quizzes passed and files handed in. Export the learner list and the
         attendance register, and attach them to the programme in the BEE tool, which matches
         people on their <strong>employee number</strong>.</p>
      <p>This is evidence, not a B-BBEE calculation. Spend, categories and points are worked out
         in the BEE tool and by your verification agency. There is no &ldquo;completed&rdquo;
         column because the academy does not record course completion as a single fact.</p>
    </div>

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>

    <?php if (!$courses): ?>
      <div class="adm-empty"><strong>No courses to show</strong></div>
    <?php else: ?>

      <?php if (count($courses) > 1): ?>
        <div class="adm-tabs">
          <?php foreach ($courses as $slug => $c): ?>
            <a class="adm-tab<?= $slug === $course ? ' is-on' : '' ?>"
               href="<?= e(evidence_qs(['course' => $slug])) ?>"><?= e((string) ($c['title'] ?? $slug)) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="GET" class="adm-search">
        <input type="hidden" name="course" value="<?= e($course) ?>">
        <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn btn-ghost" type="submit">Show</button>
        <?php if ($data['learners']): ?>
          <a class="btn btn-primary" href="<?= e(evidence_qs(['export' => 'learners'])) ?>">Export learners (CSV)</a>
          <?php if ($data['classes']): ?>
            <a class="btn btn-ghost" href="<?= e(evidence_qs(['export' => 'register'])) ?>">Export attendance register (CSV)</a>
          <?php endif; ?>
        <?php endif; ?>
      </form>

      <?php if ($noEmpNo > 0): ?>
        <p class="form-err" role="alert">
          <?= $noEmpNo ?> learner<?= $noEmpNo === 1 ? ' has' : 's have' ?> no employee number, so the
          BEE tool cannot link them automatically. Add it on
          <a href="admin-users">Accounts</a> before exporting.
        </p>
      <?php endif; ?>

      <?php if (!$data['learners']): ?>
        <div class="adm-empty">
          <strong>Nobody was enrolled on this course by <?= e($to) ?></strong>
          <p>Learners appear here once they are enrolled.</p>
        </div>
      <?php else: ?>
        <h3>Learners (<?= count($data['learners']) ?>)</h3>
        <div class="adm-scroll">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Learner</th><th>Employee no</th><th>Enrolled</th>
              <th>Classes attended</th><th>Quizzes passed</th><th>Files in / assessed</th><th>RPL</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data['learners'] as $l): ?>
              <tr>
                <td><strong><?= e(trim($l['first_name'] . ' ' . $l['last_name'])) ?></strong>
                    <?php if ($l['department']): ?><span class="adm-sub"><?= e((string) $l['department']) ?></span><?php endif; ?></td>
                <td><?= $l['employee_no'] ? e((string) $l['employee_no']) : '<span class="adm-sub">none</span>' ?></td>
                <td><?= e(substr((string) $l['enrolled_at'], 0, 10)) ?>
                    <?php if ($l['enrol_status'] !== 'active'): ?><span class="adm-sub"><?= e((string) $l['enrol_status']) ?></span><?php endif; ?></td>
                <td><?= (int) $l['attended'] ?> of <?= count($data['classes']) ?></td>
                <td><?= (int) $l['quizzes_passed'] ?> of <?= (int) $data['quizCount'] ?></td>
                <td><?= (int) $l['files_in'] ?> / <?= (int) $l['files_assessed'] ?></td>
                <td><?= $l['rpl'] === null ? '—' : (int) $l['rpl'] . ' modules' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>

        <h3>Classes held (<?= count($data['classes']) ?>)</h3>
        <?php if (!$data['classes']): ?>
          <p class="adm-sub">No register was marked for this course between <?= e($from) ?> and <?= e($to) ?>.</p>
        <?php else: ?>
          <div class="adm-scroll">
          <table class="adm-table">
            <thead><tr><th>Date</th><th>Class</th><th>Facilitator</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th><th>Not marked</th></tr></thead>
            <tbody>
              <?php foreach ($data['classes'] as $c):
                  $n = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'none' => 0];
                  foreach ($data['learners'] as $l) {
                      $st = $data['register'][(int) $c['id']][(int) $l['id']] ?? 'none';
                      $n[isset($n[$st]) ? $st : 'none']++;
                  } ?>
                <tr>
                  <td><?= e(class_when((string) $c['held_on'], $c['starts_at'], $c['ends_at'])) ?></td>
                  <td><?= e((string) $c['title']) ?><?php if ($c['venue']): ?><span class="adm-sub"><?= e((string) $c['venue']) ?></span><?php endif; ?></td>
                  <td><?= e(trim((string) $c['f_first'] . ' ' . (string) $c['f_last'])) ?: '—' ?></td>
                  <td><?= $n['present'] ?></td><td><?= $n['late'] ?></td><td><?= $n['absent'] ?></td>
                  <td><?= $n['excused'] ?></td><td><?= $n['none'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <p class="adm-foot">Every view and every export of this page is logged. The export carries
      names, employee numbers and training records — send it only to the people preparing the
      company's B-BBEE submission.</p>
  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
