<?php
declare(strict_types=1);

/* Skills: "Your learning path" (app_shell, 16 Nov 2026; from the sps-newui prototype).
 *
 * SHARED: synced to every academy. Only reachable where lib/brand.php sets
 * 'app_shell' — elsewhere it sends the learner to /my (see journey_require_shell()).
 *
 * Two tabs, after the Skilling Africa skills page Sibusiso shared:
 *   Learning path — one course at a time, drawn as the QCTO route: Part A
 *                   knowledge, Part B practicals, Part C workplace, then the
 *                   EISA. A short course is just its modules. Nothing is
 *                   padlocked: SPS does not lock modules, so a lock would say
 *                   something untrue. Upcoming steps are simply quieter.
 *   Badges        — earned from real records only (see lib/journey.php).
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

$tab = ($_GET['tab'] ?? '') === 'badges' ? 'badges' : 'path';
$cur = null;
foreach ($j['courses'] as $c) if ($c['slug'] === (string) ($_GET['c'] ?? '')) $cur = $c;
$cur = $cur ?? ($j['courses'][0] ?? null);
$nb  = $j['nextBadge'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Skills — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'skills', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<main class="dash">
  <header class="dash-title">
    <h1>Your learning <span>path</span></h1>
    <p>Build new skills, one step at a time.</p>
  </header>

  <div class="sk-tabs" role="tablist">
    <a href="skills<?= $cur ? '?c=' . e(rawurlencode($cur['slug'])) : '' ?>" role="tab" aria-selected="<?= $tab === 'path' ? 'true' : 'false' ?>" class="<?= $tab === 'path' ? 'on' : '' ?>">Learning path</a>
    <a href="skills?tab=badges" role="tab" aria-selected="<?= $tab === 'badges' ? 'true' : 'false' ?>" class="<?= $tab === 'badges' ? 'on' : '' ?>">Badges</a>
  </div>

<?php if ($tab === 'badges'): ?>

  <?php if ($nb): ?>
    <section class="bd-next">
      <span class="bd-ico"><?= journey_svg($nb['icon']) ?></span>
      <div class="bd-next-body">
        <strong>Next badge: <?= e($nb['name']) ?></strong>
        <span><?= e($nb['rule']) ?></span>
        <span class="bd-next-bar"><span class="dash-bar"><i style="width:<?= (int) round($nb['have'] / $nb['need'] * 100) ?>%"></i></span><b><?= (int) $nb['have'] ?>/<?= (int) $nb['need'] ?></b></span>
      </div>
    </section>
  <?php endif; ?>

  <div class="bd-grid">
    <?php foreach ($j['badges'] as $x): ?>
      <div class="bd<?= $x['earned'] ? ' earned' : '' ?>">
        <span class="bd-ico"><?= journey_svg($x['icon']) ?></span>
        <strong><?= e($x['name']) ?></strong>
        <span class="bd-rule"><?= e($x['rule']) ?></span>
        <span class="bd-pill"><?= $x['earned'] ? 'Earned' : 'In progress · ' . (int) $x['have'] . '/' . (int) $x['need'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="dash-muted sk-foot">Badges are for encouragement. They are not credits, and they are not part of your assessment.</p>

<?php elseif (!$cur): ?>

  <p class="dash-muted">You are not on a course yet. <a href="dashboard">Choose one on Home</a>.</p>

<?php else: ?>

  <div class="sk-courses">
    <?php foreach ($j['courses'] as $c): ?>
      <a href="skills?c=<?= e(rawurlencode($c['slug'])) ?>" class="sk-course<?= $c['slug'] === $cur['slug'] ? ' on' : '' ?>">
        <span class="sk-ring" style="--p:<?= (int) $c['pct'] ?>"><b><?= (int) $c['pct'] ?>%</b></span>
        <span><strong><?= e($c['title']) ?></strong><small><?= (int) $c['done'] ?>/<?= (int) $c['total'] ?> steps</small></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="sk-path" data-course="<?= e($cur['slug']) ?>">
    <?php foreach ($cur['parts'] as $pi => $p):
      $hereHere = $cur['here'] !== null && $cur['here'][0] === $pi; ?>
      <section class="sk-part<?= $hereHere ? ' here' : '' ?><?= $p['done'] === $p['total'] ? ' complete' : '' ?>">
        <span class="sk-part-key"><?= e($p['key']) ?></span>
        <div class="sk-part-body">
          <span class="sk-kicker"><?= $cur['qual'] ? 'Part ' . e($p['key']) : 'Your course' ?><?php if ($hereHere): ?> <em>You are here</em><?php endif; ?></span>
          <strong><?= e($p['name']) ?></strong>
          <small><?= e($p['how']) ?></small>
          <span class="sk-part-bar"><span class="dash-bar"><i style="width:<?= $p['total'] ? (int) round($p['done'] / $p['total'] * 100) : 0 ?>%"></i></span><b><?= (int) $p['done'] ?>/<?= (int) $p['total'] ?></b></span>
        </div>
      </section>

      <?php /* Only the part you are on is open; the rest fold away so the page stays short. */ ?>
      <details class="sk-fold"<?= $hereHere || ($cur['here'] === null && $pi === 0) ? ' open' : '' ?>>
      <summary><span>Show <?= (int) $p['total'] ?> <?= $p['total'] === 1 ? 'step' : 'steps' ?></span><span>Hide steps</span></summary>
      <ol class="sk-nodes">
        <?php foreach ($p['nodes'] as $ni => $n):
          $isHere = $cur['here'] === [$pi, $ni]; ?>
          <li class="sk-node <?= e($n['state']) ?><?= $isHere ? ' current' : '' ?> s<?= $ni % 4 ?>">
            <a href="<?= e($n['href']) ?>"<?= $p['key'] === 'A' ? ' data-mod="' . e($n['code']) . '"' : '' ?>>
              <?php if ($isHere): ?><span class="sk-start"><?= journey_svg('star') ?> <?= $n['state'] === 'part' ? 'Continue' : 'Start' ?></span><?php endif; ?>
              <span class="sk-dot"><?= $n['state'] === 'done' ? journey_svg('check') : ($n['code'] === 'WM' ? journey_svg('brief') : e(preg_replace('/^.*-0?/', '', $n['code']))) ?></span>
              <span class="sk-label"><b><?= e($n['code'] === 'WM' ? '' : $n['code']) ?></b> <?= e($n['title']) ?></span>
              <span class="sk-sub"><?= e($n['sub']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
      </details>
    <?php endforeach; ?>

    <section class="sk-end">
      <span class="sk-end-ico"><?= journey_svg('flag') ?></span>
      <div>
        <strong><?= $cur['qual'] ? 'Final assessment: the EISA' : 'Finish the course' ?></strong>
        <small><?= $cur['qual']
            ? 'The external integrated summative assessment, set by the QCTO. Nothing exempts it, not RPL and not the Google certificate.'
            : e($cur['assessed']) ?></small>
      </div>
    </section>
  </div>

<?php endif; ?>
</main>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('pm-modules.js')) ?>"></script>
<script src="<?= e(asset('po-modules.js')) ?>"></script>
<script src="<?= e(asset('nvc-modules.js')) ?>"></script>
<script src="<?= e(asset('module-info.js')) ?>"></script>
</body></html>
