<?php
declare(strict_types=1);

/* The notice board, as a learner sees it.
 *
 * Read-only. What is on it is posted from admin-notices.php by an administrator
 * or by a facilitator for their own course; this page shows everybody-notices
 * plus those for the courses the signed-in learner is enrolled on, newest first
 * with pinned ones on top. See lib/notices.php for who may post what.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/notices.php';
require __DIR__ . '/lib/sessions.php';
require __DIR__ . '/lib/classes.php';
require __DIR__ . '/lib/chrome.php';

$me = require_user();

$items = db_optional(fn() => notices_for_learner((int) $me['id']), []) ?: [];
$rooms = db_optional(fn() => learner_course_links((int) $me['id']), []) ?: [];

audit('notices.viewed', 'notices', (int) $me['id'], count($items) . ' notice(s)');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notice board — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('learner', ['active' => 'notices', 'admin' => $me['role'] === 'admin', 'trainer' => $me['role'] === 'trainer']); ?>

<section class="section-dark page-top">
  <div class="wrap">
    <div class="sec-head sec-head-wide">
      <span class="eyebrow"><?= e(brand('academy')) ?></span>
      <h2 class="lede-h">Notice board</h2>
      <p>News from the academy and your facilitators: classes that will be held in Google
        Classroom, changed dates, and what is due. Check it before every class.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>

    <?php if ($rooms): ?>
      <div class="nb-rooms">
        <strong>Your Google Classroom</strong>
        <?php foreach ($rooms as $r): ?>
          <a class="btn btn-ghost" href="<?= e((string) $r['url']) ?>" target="_blank" rel="noopener">
            <?= e((string) ($r['label'] ?? '') !== '' ? (string) $r['label'] : learner_course_title((string) $r['course_slug'])) ?> →</a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$items): ?>
      <div class="my-empty">
        <strong>Nothing on the board right now</strong>
        <p>When there is news, such as the next class or a change of date, it will appear here.</p>
      </div>
    <?php else: ?>
      <div class="nb-list">
        <?php foreach ($items as $n): ?>
          <article class="nb-card<?= !empty($n['pinned']) ? ' is-pinned' : '' ?>">
            <header>
              <?php if (!empty($n['pinned'])): ?><span class="nb-pin">Pinned</span><?php endif; ?>
              <h3><?= e((string) $n['title']) ?></h3>
              <p class="nb-meta"><?= e(notice_audience($n)) ?> · <?= e(notice_when((string) $n['created_at'])) ?>
                <?php $by = trim((string) ($n['by_first'] ?? '') . ' ' . (string) ($n['by_last'] ?? '')); ?>
                <?= $by !== '' ? ' · ' . e($by) : '' ?></p>
            </header>
            <div class="nb-body"><?= nl2br(e((string) $n['body'])) ?></div>
            <?php if (!empty($n['link_url'])): ?>
              <p><a class="btn btn-primary" href="<?= e((string) $n['link_url']) ?>" target="_blank" rel="noopener">
                <?= e((string) ($n['link_label'] ?? 'Open the link')) ?> →</a></p>
            <?php endif; ?>
            <?php if (!empty($n['expires_on'])): ?>
              <p class="nb-meta">Shown until <?= e((string) $n['expires_on']) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
