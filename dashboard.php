<?php
declare(strict_types=1);

/* Home: the signed-in learner's first page (app_shell, 16 Nov 2026; from the sps-newui prototype).
 *
 * SHARED: synced to every academy. Only reachable where lib/brand.php sets
 * 'app_shell' — elsewhere it sends the learner to /my (see journey_require_shell()).
 *
 * Deliberately short, after the signed-in Skilling Africa page Sibusiso shared
 * ("keep it simple, don't confuse it with a lot of information"):
 *   greeting → this week → your next objectives → new for you.
 * The full module path lives on Skills, the numbers on Account. Every figure
 * comes from lib/journey.php, which reads only real records — no XP.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/notices.php';
require __DIR__ . '/lib/rpl.php';
require __DIR__ . '/lib/journey.php';
require __DIR__ . '/lib/chrome.php';

$me  = require_user();
journey_require_shell();
$uid = (int) $me['id'];

/* Each academy has its own course pictures (SPS and Maziv have "-v2" mixed-group
   banners, the rest the originals), so the picture is whichever exists here. */
$dashImg = static function (string $slug): string {
    foreach (["images/course-$slug-v2.jpg", "images/course-$slug.jpg"] as $p) {
        if (is_file(__DIR__ . '/' . $p)) return $p;
    }
    return 'images/hero.jpg';
};

$j    = journey_for($uid);
$next = journey_next($j);
$hour = (int) $j['now']->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$notice  = (db_optional(fn() => notices_for_learner($uid, 1), []) ?: [])[0] ?? null;
$onSlugs = array_map(fn($e) => (string) $e['course_slug'], $j['enrolments']);
$canAdd  = $me['role'] === 'learner' && brand_has('self_signup')
    ? array_values(array_diff(LEARNER_SELF_ENROL_COURSES, $onSlugs)) : [];
$nb = $j['nextBadge'];

audit('learner.dashboard_viewed', 'users', $uid, 'home · ' . count($j['courses']) . ' course(s)');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'home', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<main class="dash">
  <?php if (($_GET['welcome'] ?? '') === '1'): ?>
    <p class="dash-welcome" role="status">Your account is ready. Your first step is below, and every guide and workbook is under <a href="files">My files</a>.</p>
  <?php endif; ?>

  <header class="dash-hello">
    <span><?= e($greet) ?></span>
    <h1><?= e((string) $me['first_name']) ?></h1>
  </header>

  <section class="dash-week" aria-label="This week">
    <div class="dw-streak">
      <span class="dw-flame"><?= journey_svg('flame') ?></span>
      <div>
        <strong><?= (int) $j['streak'] ?> day streak</strong>
        <span><?= $j['streak'] > 0 ? 'Keep it going today' : 'Study a topic today to start one' ?></span>
      </div>
    </div>
    <ol class="dw-days">
      <?php foreach ($j['week'] as $d): ?>
        <li class="<?= $d['done'] ? 'done' : '' ?><?= $d['today'] ? ' today' : '' ?><?= $d['future'] ? ' future' : '' ?>">
          <span class="dot"><?= $d['done'] ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>' : '' ?></span>
          <span class="lbl"><?= e($d['label']) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
    <div class="dw-count"><strong><?= (int) $j['topicsWeek'] ?></strong><span>topic<?= $j['topicsWeek'] === 1 ? '' : 's' ?> this week</span></div>
  </section>

  <section class="dash-obj">
    <h2>Your next <span>objectives</span></h2>
    <div class="obj-list">
      <?php if ($next): ?>
        <a class="obj" href="<?= e($next['href']) ?>" data-mod="<?= $next['partKey'] === 'A' ? e($next['code']) : '' ?>">
          <span class="obj-ico o"><?= journey_svg($next['partKey'] === 'A' ? 'book' : ($next['partKey'] === 'B' ? 'tool' : 'brief')) ?></span>
          <span class="obj-body">
            <small><?= e($next['course']) ?> · <?= e($next['part']) ?></small>
            <strong><?= e($next['code'] === 'WM' ? $next['title'] : $next['code'] . ' · ' . $next['title']) ?></strong>
            <span><?= e(!empty($next['next']) ? 'Up next: ' . $next['next'] : $next['sub']) ?></span>
          </span>
          <span class="obj-go"><?= $next['partKey'] === 'A' ? 'Continue' : 'Open' ?> <?= journey_svg('chev') ?></span>
        </a>
      <?php elseif ($j['courses']): ?>
        <div class="obj"><span class="obj-ico g"><?= journey_svg('flag') ?></span>
          <span class="obj-body"><strong>Every step is done</strong><span>Your facilitator will be in touch about the final assessment.</span></span></div>
      <?php endif; ?>

      <?php if ($nb): ?>
        <a class="obj" href="skills?tab=badges">
          <span class="obj-ico y"><?= journey_svg($nb['icon']) ?></span>
          <span class="obj-body">
            <small>Next badge</small>
            <strong><?= e($nb['name']) ?></strong>
            <span class="obj-bar"><span class="dash-bar"><i style="width:<?= (int) round($nb['have'] / $nb['need'] * 100) ?>%"></i></span><b><?= (int) $nb['have'] ?>/<?= (int) $nb['need'] ?></b></span>
          </span>
          <span class="obj-go">Badges <?= journey_svg('chev') ?></span>
        </a>
      <?php endif; ?>

      <?php if ($notice): ?>
        <a class="obj" href="notices">
          <span class="obj-ico g"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg></span>
          <span class="obj-body">
            <small>Notice board</small>
            <strong><?= e((string) $notice['title']) ?></strong>
            <span><?= e(mb_strimwidth((string) $notice['body'], 0, 110, '…')) ?></span>
          </span>
          <span class="obj-go">Read <?= journey_svg('chev') ?></span>
        </a>
      <?php endif; ?>

      <?php if (!$j['courses']): ?>
        <p class="dash-muted">Choose a course below to get your first step.</p>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($canAdd): ?>
    <section class="dash-course">
      <div class="dash-head"><h2><?= $j['courses'] ? 'New for you' : 'Choose your first course' ?></h2></div>
      <div class="dash-add">
        <?php foreach ($canAdd as $slug): ?>
          <form method="POST" action="my" class="dash-add-item">
            <?= csrf_field() ?>
            <input type="hidden" name="a" value="enrol_self">
            <input type="hidden" name="course" value="<?= e($slug) ?>">
            <span class="dai-img" style="background-image:url('<?= e($dashImg((string) $slug)) ?>')"></span>
            <span class="dai-body">
              <strong><?= e(learner_course_title($slug)) ?></strong>
              <small><?= e((string) (learner_catalogue()[$slug]['note'] ?? '')) ?></small>
              <button type="submit" class="btn btn-primary btn-sm">Add this course</button>
            </span>
          </form>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('pm-modules.js')) ?>"></script>
<script src="<?= e(asset('po-modules.js')) ?>"></script>
<script src="<?= e(asset('nvc-modules.js')) ?>"></script>
<script src="<?= e(asset('module-info.js')) ?>"></script>
</body></html>
