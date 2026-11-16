<?php
declare(strict_types=1);

/* A learner's journey: everything Home, Skills and Account show, worked out
 * once from the real records (app_shell, 16 Nov 2026; from the sps-newui prototype).
 *
 * SHARED: synced to every academy. Only reachable where lib/brand.php sets
 * 'app_shell' — elsewhere it sends the learner to /my (see journey_require_shell()).
 *
 * Follows the QCTO shape of an occupational qualification rather than a
 * points game:
 *   Part A  Knowledge modules  — topics ticked, the 80% topic checks
 *   Part B  Practical modules  — the practical workbook handed in
 *   Part C  Workplace          — the workplace logbook, signed off by the facilitator
 *   Then    the EISA           — the external final assessment, which nothing exempts
 * A short course has no such split and no EISA: it is one list of modules.
 *
 * No XP and no "hours learning": the platform records neither, and a made-up
 * number is the first thing on a page that is not true. Badges are earned
 * only from things that are in the database.
 *
 * Needs (already required by the page): bootstrap, db, learner, curriculum, rpl.
 */

require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/logbook.php';

const JOURNEY_TZ = 'Africa/Johannesburg';

/** Line-icon paths, shared by the pages. */
const JOURNEY_ICON = [
    'flame'  => '<path d="M12 2c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2-4.5.3 2 1.3 3 2.5 3-1-3 .5-6 .5-8.5z"/>',
    'rocket' => '<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2M14 4c3-1 6-1 6-1s0 3-1 6l-6 6-5-5z"/><circle cx="15" cy="9" r="1.5"/>',
    'cap'    => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
    'check'  => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
    'star'   => '<path d="M12 3l2.6 5.6L20 9.3l-4 4.1 1 5.6-5-2.8-5 2.8 1-5.6-4-4.1 5.4-.7z"/>',
    'book'   => '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM8 7h7"/>',
    'upload' => '<path d="M12 16V4M7 9l5-5 5 5M5 20h14"/>',
    'tool'   => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.5-.5-2.5z"/>',
    'brief'  => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
    'flag'   => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
    'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'chev'   => '<path d="M9 6l6 6-6 6"/>',
];

/** Home, Skills and Account exist only inside the app frame. A site without
 *  'app_shell' in lib/brand.php keeps /my as the learner's page, so a stray
 *  link or an old bookmark lands there instead of on a frameless page. */
function journey_require_shell(): void
{
    if (!brand_has('app_shell')) {
        header('Location: my', true, 303);
        exit;
    }
}

function journey_svg(string $name, string $extra = ''): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
        . 'stroke-linejoin="round" aria-hidden="true"' . $extra . '>' . (JOURNEY_ICON[$name] ?? '') . '</svg>';
}

/** @return array<string,mixed> */
function journey_for(int $uid): array
{
    $tz  = new DateTimeZone(JOURNEY_TZ);
    $utc = new DateTimeZone('UTC');
    $now = new DateTime('now', $tz);
    $monday = (clone $now)->modify('monday this week')->setTime(0, 0);

    /* ---- Study days ------------------------------------------------------ */
    $days = [];            // Y-m-d => true, any activity
    $topicDays = [];       // Y-m-d => topics ticked that day
    $topicsAll = 0;
    foreach ((db_optional(fn() => db_all('SELECT completed_at AS t, item_code FROM learner_progress
                WHERE tenant_id = ? AND user_id = ?', [tenant_id(), $uid]), []) ?: []) as $r) {
        $k = (new DateTime((string) $r['t'], $utc))->setTimezone($tz)->format('Y-m-d');
        $days[$k] = true;
        if ((string) $r['item_code'] !== '') { $topicDays[$k] = ($topicDays[$k] ?? 0) + 1; $topicsAll++; }
    }
    $passed = []; $perfect = 0;
    foreach ((db_optional(fn() => db_all('SELECT a.quiz_id, a.submitted_at AS t, a.score_count, a.question_count, q.pass_pct
                FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id
               WHERE a.tenant_id = ? AND a.user_id = ?', [tenant_id(), $uid]), []) ?: []) as $r) {
        $k = (new DateTime((string) $r['t'], $utc))->setTimezone($tz)->format('Y-m-d');
        $days[$k] = true;
        $n = max(1, (int) $r['question_count']);
        $pct = (int) floor((int) $r['score_count'] * 100 / $n);
        if ($pct >= (int) ($r['pass_pct'] ?? 80)) $passed[(int) $r['quiz_id']] = true;
        if ((int) $r['score_count'] >= $n) $perfect++;
    }

    $streak = 0;
    $c = clone $now;
    if (!isset($days[$c->format('Y-m-d')])) $c->modify('-1 day');   // today not done yet
    while (isset($days[$c->format('Y-m-d')])) { $streak++; $c->modify('-1 day'); }

    $best = 0; $run = 0; $prev = null;
    $keys = array_keys($days); sort($keys);
    foreach ($keys as $k) {
        $run = ($prev !== null && (new DateTime($prev))->modify('+1 day')->format('Y-m-d') === $k) ? $run + 1 : 1;
        $best = max($best, $run); $prev = $k;
    }

    $week = []; $topicsWeek = 0;
    for ($i = 0; $i < 7; $i++) {
        $d = (clone $monday)->modify("+$i day");
        $k = $d->format('Y-m-d');
        $n = $topicDays[$k] ?? 0;
        $topicsWeek += $n;
        $week[] = ['label' => $d->format('D'), 'done' => isset($days[$k]), 'topics' => $n,
                   'today' => $k === $now->format('Y-m-d'), 'future' => $d > $now];
    }

    /* ---- Courses as QCTO paths ------------------------------------------ */
    $enrolments = db_optional(fn() => learner_enrolments($uid), []) ?: [];
    $courses = [];
    $modsDone = 0; $uploads = 0; $workbooksIn = 0; $practicalsTotal = 0; $practicalsIn = 0; $logMinutes = 0; $knowledgeComplete = false;
    foreach ($enrolments as $en) {
        $slug = (string) $en['course_slug'];
        if (!learner_course_tracked($slug)) continue;
        $tree = db_optional(fn() => learner_progress_tree($uid, $slug), []) ?: [];
        $rpl  = rpl_default_route($slug) !== null ? (db_optional(fn() => rpl_credits_for($uid, $slug), []) ?: []) : [];
        $hist = db_optional(fn() => submission_history($uid, $slug), []) ?: [];
        $poe  = db_optional(fn() => poe_for_learner($uid, $slug), []) ?: [];
        $uploads += count($hist);
        foreach (array_keys($hist) as $hk) if (str_ends_with((string) $hk, ":workbook")) $workbooksIn++;
        $q = $slug === 'project-management' ? '' : '&c=' . rawurlencode($slug);

        $partA = [];
        foreach (curriculum_modules($slug) as $mid => $m) {
            $total = count($m['topics']); $ticked = 0; $nextTopic = null;
            foreach ($m['topics'] as $tc => $name) {
                if (!empty($tree[$mid]['topics'][$tc])) $ticked++;
                elseif ($nextTopic === null) $nextTopic = (string) $name;
            }
            $rec = isset($rpl[$mid]);
            $state = $rec || ($total && $ticked >= $total) ? 'done' : ($ticked > 0 ? 'part' : 'new');
            if ($state === 'done') $modsDone++;
            $partA[] = ['code' => (string) $mid, 'title' => (string) $m['title'], 'state' => $state,
                        'ticked' => $ticked, 'total' => $total, 'rpl' => $rec, 'next' => $nextTopic,
                        'href' => 'module?m=' . rawurlencode((string) $mid) . $q,
                        'sub' => $rec ? 'Recognised (RPL)' : $ticked . ' of ' . $total . ' topics'];
        }
        $aDone = count(array_filter($partA, fn($n) => $n['state'] === 'done'));
        if ($partA && $aDone === count($partA)) $knowledgeComplete = true;

        $pract = submission_practicals($slug);
        $isQual = $pract !== [];
        $practicalsTotal += count($pract);
        $parts = [];
        $parts[] = ['key' => 'A', 'name' => $isQual ? 'Knowledge modules' : 'Modules',
                    'how' => $isQual ? 'Work through each topic and pass its check at 80%.' : 'Work through each topic and its check.',
                    'nodes' => $partA, 'done' => $aDone, 'total' => count($partA)];
        if ($isQual) {
            $partB = [];
            foreach ($pract as $code => $title) {
                $in = isset($hist[$code . ':practical']) || isset($poe[$code . ':practical']);
                $assessed = ($poe[$code . ':practical']['status'] ?? '') === 'assessed';
                if ($in) $practicalsIn++;
                $partB[] = ['code' => $code, 'title' => $title, 'state' => $in ? 'done' : 'new',
                            'href' => 'submit?c=' . rawurlencode($slug), 'sub' => $assessed ? 'Assessed' : ($in ? 'Handed in' : 'Practical workbook')];
            }
            $mins = (int) (db_optional(fn() => logbook_total_minutes($uid, $slug), 0) ?? 0);
            $logMinutes += $mins;
            $signed = false;
            foreach ($poe as $k => $r) if (str_ends_with((string) $k, ':logbook') && ($r['status'] ?? '') === 'assessed') $signed = true;
            $partC = [['code' => 'WM', 'title' => 'Workplace logbook', 'state' => $signed ? 'done' : ($mins > 0 ? 'part' : 'new'),
                       'href' => 'logbook?c=' . rawurlencode($slug),
                       'sub' => $signed ? 'Signed off' : ($mins > 0 ? logbook_hours($mins) . ' logged' : 'Log your workplace hours')]];
            $parts[] = ['key' => 'B', 'name' => 'Practical modules', 'how' => 'Do each practical and hand in its workbook.',
                        'nodes' => $partB, 'done' => count(array_filter($partB, fn($n) => $n['state'] === 'done')), 'total' => count($partB)];
            $parts[] = ['key' => 'C', 'name' => 'Workplace experience', 'how' => 'Record your work in the logbook. Your facilitator signs it off.',
                        'nodes' => $partC, 'done' => $signed ? 1 : 0, 'total' => 1];
        }

        /* "You are here": the first part not finished, and its first node not finished. */
        $here = null;
        foreach ($parts as $pi => $p) {
            foreach ($p['nodes'] as $ni => $n) {
                if ($n['state'] !== 'done') { $here = [$pi, $ni]; break 2; }
            }
        }
        $all = array_sum(array_column($parts, 'total'));
        $got = array_sum(array_column($parts, 'done'));
        $courses[] = ['slug' => $slug, 'title' => (string) $en['course_title'], 'qual' => $isQual,
                      'parts' => $parts, 'here' => $here, 'pct' => $all ? (int) round($got / $all * 100) : 0,
                      'done' => $got, 'total' => $all, 'modsTotal' => count($partA),
                      'assessed' => learner_assessment_route($slug)];
    }

    /* ---- Badges ----------------------------------------------------------- */
    $b = static fn(string $id, string $name, string $rule, string $icon, int $have, int $need): array
        => ['id' => $id, 'name' => $name, 'rule' => $rule, 'icon' => $icon,
            'have' => min($have, $need), 'need' => $need, 'earned' => $have >= $need];
    $badges = [
        $b('first-steps', 'First Steps',      'Finish your first topic',             'rocket', $topicsAll, 1),
        $b('consistency', 'Consistency',      'Study 3 days in a row',               'flame',  $best, 3),
        $b('weekly',      'Weekly Warrior',   'Study 7 days in a row',               'flame',  $best, 7),
        $b('unstoppable', 'Unstoppable',      'Study 30 days in a row',              'flame',  $best, 30),
        $b('first-mod',   'First Module',     'Complete your first module',          'cap',    $modsDone, 1),
        $b('high-five',   'High Five',        'Complete 5 modules',                  'cap',    $modsDone, 5),
        $b('knowledge',   'Knowledge Ready',  'Complete every knowledge module on a course', 'book', $knowledgeComplete ? 1 : 0, 1),
        $b('sharp',       'Sharp Mind',       'Pass 5 topic checks at 80% or more',  'check',  count($passed), 5),
        $b('top-marks',   'Top Marks',        'Score 100% on a topic check',         'star',   $perfect, 1),
        $b('handed-in',   'Handed In',        'Hand in your first workbook',         'upload', $uploads, 1),
        $b('practical',   'Practical Pro',    'Hand in 5 practical workbooks',       'tool',   $practicalsIn, 5),
        $b('on-the-job',  'On the Job',       'Log 10 hours in your workplace logbook', 'brief', intdiv($logMinutes, 60), 10),
    ];
    $nextBadge = null;
    foreach ($badges as $x) {
        if ($x['earned']) continue;
        if ($nextBadge === null || $x['have'] / $x['need'] > $nextBadge['have'] / $nextBadge['need']) $nextBadge = $x;
    }

    return ['now' => $now, 'streak' => $streak, 'best' => $best, 'week' => $week, 'topicsWeek' => $topicsWeek,
            'topicsAll' => $topicsAll, 'checksPassed' => count($passed), 'modsDone' => $modsDone,
            'uploads' => $uploads, 'workbooksIn' => $workbooksIn, 'practicalsTotal' => $practicalsTotal, 'practicalsIn' => $practicalsIn, 'logMinutes' => $logMinutes,
            'courses' => $courses, 'enrolments' => $enrolments, 'badges' => $badges, 'nextBadge' => $nextBadge,
            'earned' => count(array_filter($badges, fn($x) => $x['earned']))];
}

/** The next node to work on, across every course, with its course. */
function journey_next(array $j): ?array
{
    foreach ($j['courses'] as $c) {
        if ($c['here'] === null) continue;
        [$pi, $ni] = $c['here'];
        return $c['parts'][$pi]['nodes'][$ni] + ['course' => $c['title'], 'slug' => $c['slug'],
                'part' => $c['parts'][$pi]['name'], 'partKey' => $c['parts'][$pi]['key']];
    }
    return null;
}
