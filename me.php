<?php
declare(strict_types=1);

/* Account: the learner's own page (app_shell, 16 Nov 2026; from the sps-newui prototype).
 *
 * SHARED: synced to every academy. Only reachable where lib/brand.php sets
 * 'app_shell' — elsewhere it sends the learner to /my (see journey_require_shell()).
 *
 * After the Skilling Africa profile page Sibusiso shared: a profile card with
 * four numbers, this week, badges, learning paths. Their XP, "time learning"
 * and sound/appearance preferences are left out — the platform records none of
 * them. In their place, the one thing a QCTO learner must keep track of: the
 * portfolio of evidence (workbooks, practicals, logbook). Editing details and
 * the password stay on /my#account, which already does both.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/rpl.php';
require __DIR__ . '/lib/journey.php';
require __DIR__ . '/lib/chrome.php';

$me  = require_user();
journey_require_shell();
$uid = (int) $me['id'];
$j   = journey_for($uid);

$name = trim((string) $me['first_name'] . ' ' . (string) $me['last_name']);
$ini  = mb_strtoupper(mb_substr((string) $me['first_name'], 0, 1) . mb_substr((string) $me['last_name'], 0, 1));
$all  = array_sum(array_column($j['courses'], 'total'));
$got  = array_sum(array_column($j['courses'], 'done'));
$pct  = $all ? (int) round($got / $all * 100) : 0;
$maxDay = max(1, ...array_column($j['week'], 'topics'));
$qual = array_values(array_filter($j['courses'], fn($c) => $c['qual']));
$wbTotal = 0;
foreach ($qual as $c) $wbTotal += $c['modsTotal'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'account', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<main class="dash">
  <div class="ac-top">
    <section class="ac-hero">
      <a href="my#account" class="ac-edit">Edit details</a>
      <span class="ac-av"><?= e($ini) ?></span>
      <h1><?= e($name) ?></h1>
      <div class="ac-chips">
        <?php foreach ($j['courses'] as $c): ?><span><?= e($c['title']) ?></span><?php endforeach; ?>
        <?php if (!$j['courses']): ?><span>No course yet</span><?php endif; ?>
      </div>
      <span class="ac-prog"><span class="dash-bar"><i style="width:<?= $pct ?>%"></i></span><b><?= $got ?>/<?= $all ?> steps</b></span>
      <div class="ac-stats">
        <div><span class="ac-si o"><?= journey_svg('cap') ?></span><strong><?= (int) $j['modsDone'] ?></strong><small>Modules</small></div>
        <div><span class="ac-si r"><?= journey_svg('flame') ?></span><strong><?= (int) $j['streak'] ?>d</strong><small>Streak</small></div>
        <div><span class="ac-si y"><?= journey_svg('book') ?></span><strong><?= (int) $j['topicsAll'] ?></strong><small>Topics</small></div>
        <div><span class="ac-si g"><?= journey_svg('check') ?></span><strong><?= (int) $j['checksPassed'] ?></strong><small>Checks passed</small></div>
      </div>
    </section>

    <div class="ac-side">
      <section class="dash-card ac-week">
        <div class="dash-head"><h2><?= (int) $j['topicsWeek'] ?> topic<?= $j['topicsWeek'] === 1 ? '' : 's' ?> this week</h2></div>
        <div class="ac-bars">
          <?php foreach ($j['week'] as $d): ?>
            <span class="<?= $d['today'] ? 'today' : '' ?>"><i style="height:<?= $d['topics'] ? max(12, (int) round($d['topics'] / $maxDay * 100)) : 0 ?>%" title="<?= (int) $d['topics'] ?> topics"></i><small><?= e($d['label']) ?></small></span>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="dash-card ac-badges">
        <div class="dash-head"><h2>Badges</h2><a href="skills?tab=badges"><?= (int) $j['earned'] ?> of <?= count($j['badges']) ?> · View all <?= journey_svg('chev') ?></a></div>
        <div class="ac-badge-row">
          <?php foreach (array_slice($j['badges'], 0, 5) as $x): ?>
            <span class="<?= $x['earned'] ? 'earned' : '' ?>" title="<?= e($x['rule']) ?>"><i><?= journey_svg($x['icon']) ?></i><small><?= e($x['name']) ?></small></span>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>

  <section class="dash-card ac-block">
    <div class="dash-head"><h2>Learning paths</h2><a href="skills">View all <?= journey_svg('chev') ?></a></div>
    <div class="ac-paths">
      <?php foreach ($j['courses'] as $c): ?>
        <a href="skills?c=<?= e(rawurlencode($c['slug'])) ?>" class="ac-path">
          <span class="ac-path-top"><strong><?= e($c['title']) ?></strong><b><?= (int) $c['pct'] ?>%</b></span>
          <span class="dash-bar"><i style="width:<?= (int) $c['pct'] ?>%"></i></span>
          <small><?= $c['qual'] ? 'Knowledge, practical and workplace · ' : '' ?><?= (int) $c['done'] ?> of <?= (int) $c['total'] ?> steps</small>
        </a>
      <?php endforeach; ?>
      <?php if (!$j['courses']): ?><p class="dash-muted">Add a course on <a href="dashboard">Home</a> to start a path.</p><?php endif; ?>
    </div>
  </section>

  <?php if ($qual): ?>
    <section class="dash-card ac-block">
      <div class="dash-head"><h2>Portfolio of evidence</h2></div>
      <p class="dash-muted ac-note">What your facilitator needs from you before the final assessment. Hand in the workbooks and keep the logbook up to date.</p>
      <div class="ac-poe">
        <a href="submit"><span class="ac-si o"><?= journey_svg('upload') ?></span><span><strong><?= (int) $j['workbooksIn'] ?> of <?= $wbTotal ?></strong><small>Knowledge workbooks handed in</small></span></a>
        <a href="submit"><span class="ac-si y"><?= journey_svg('tool') ?></span><span><strong><?= (int) $j['practicalsIn'] ?> of <?= (int) $j['practicalsTotal'] ?></strong><small>Practical workbooks handed in</small></span></a>
        <a href="logbook"><span class="ac-si g"><?= journey_svg('brief') ?></span><span><strong><?= e($j['logMinutes'] ? logbook_hours((int) $j['logMinutes']) : '0 hours') ?></strong><small>Logged in your workplace logbook</small></span></a>
      </div>
    </section>
  <?php endif; ?>

  <section class="dash-card ac-block">
    <div class="dash-head"><h2>Your account</h2></div>
    <div class="ac-rows">
      <div><small>Email</small><strong><?= e((string) $me['email']) ?></strong></div>
      <a href="my#account"><small>Name and password</small><strong>Change your details <?= journey_svg('chev') ?></strong></a>
      <a href="notices"><small>Notice board</small><strong>Class dates and announcements <?= journey_svg('chev') ?></strong></a>
      <a href="contact"><small>Help</small><strong>Contact the academy <?= journey_svg('chev') ?></strong></a>
    </div>
  </section>
</main>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
</body></html>
