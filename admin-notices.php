<?php
declare(strict_types=1);

/* Post to the notice board.
 *
 * An administrator may post to everybody or to any course. A facilitator may
 * post to the courses they are assigned to and nowhere else, which is the
 * narrow write argued for in lib/notices.php. The gate is notice_may_post() /
 * notice_may_edit(), checked inside notice_save() and notice_delete() against
 * the audience that was actually POSTed, not against what the form offered.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/notices.php';
require __DIR__ . '/lib/chrome.php';

$me = require_staff();

$isAdmin = $me['role'] === 'admin';

/* The audiences this person may post to, as slug => title. '' is everybody. */
$audiences = [];
if ($isAdmin) $audiences[''] = 'Everybody on the academy';
foreach (learner_catalogue() as $slug => $c) {
    if (($c['title'] ?? null) === null) continue;
    if (!$isAdmin && !may_see_course($slug, $me)) continue;
    $audiences[$slug] = (string) $c['title'];
}

$notice = '';
$errors = [];

if (is_post()) {
    if (!csrf_valid()) {
        $errors[] = 'That form had expired. Nothing was saved, so please try again.';
    } else {
        $do  = (string) ($_POST['do'] ?? '');
        $id  = ($_POST['id'] ?? '') !== '' ? (int) $_POST['id'] : null;
        $res = null;
        if ($do === 'save') {
            $res = db_optional(fn() => notice_save($_POST, (int) $me['id'], $id), null);
        } elseif ($do === 'delete' && $id !== null) {
            $res = db_optional(fn() => notice_delete($id), null);
        }
        if ($res === null && $do !== '') {
            $errors[] = db_schema_notice();
        } elseif (is_array($res)) {
            [$ok, $msg] = $res;
            if ($ok) { $notice = $msg; $_GET['edit'] = ''; } else { $errors[] = $msg; }
        }
        csrf_rotate();
    }
}

$items   = db_optional(fn() => notices_for_staff($me), []) ?: [];
$editing = null;
if (($_GET['edit'] ?? '') !== '') {
    $editing = db_optional(fn() => notice_get((int) $_GET['edit']));
    if ($editing !== null && !notice_may_edit($editing, $me)) $editing = null;
}
$today = gmdate('Y-m-d');
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
<?php chrome_nav('admin', ['active' => 'notices', 'name' => $me['first_name']]); ?>

<section class="section page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Announcements</span>
        <h2>Notice board</h2>
      </div>
    </div>

    <div class="mat-intro">
      <p>Tell learners what is happening: a class that will be held in Google Classroom, a
         changed date, what is due. Paste the Classroom or Meet link into the notice and learners
         get a button that takes them straight there.
         <?php if (!$isAdmin): ?>You can post to the courses you facilitate.<?php endif; ?></p>
    </div>

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>
    <?php if ($notice !== ''): ?><p class="adm-notice" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php foreach ($errors as $er): ?><p class="form-err" role="alert"><?= e($er) ?></p><?php endforeach; ?>

    <?php if (!$audiences): ?>
      <div class="adm-empty">
        <strong>No courses assigned to you</strong>
        <p>An administrator assigns the courses you facilitate. Until then there is nobody to post to.</p>
      </div>
    <?php else: ?>

      <div class="adm-block">
        <h3><?= $editing ? 'Edit this notice' : 'New notice' ?></h3>
        <form method="POST" class="form">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="save">
          <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>

          <div class="field">
            <label for="nt-aud">Who is it for</label>
            <select id="nt-aud" name="course_slug">
              <?php foreach ($audiences as $slug => $title): ?>
                <option value="<?= e((string) $slug) ?>"
                  <?= (string) ($editing['course_slug'] ?? array_key_first($audiences)) === (string) $slug ? 'selected' : '' ?>>
                  <?= e($title) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="nt-title">Title</label>
            <input id="nt-title" type="text" name="title" maxlength="<?= NOTICE_TITLE_MAX ?>" required
                   placeholder="Live class on Thursday, 15:00 in Google Classroom"
                   value="<?= e((string) ($editing['title'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="nt-body">What learners need to know</label>
            <textarea id="nt-body" name="body" rows="5" maxlength="<?= NOTICE_BODY_MAX ?>" required
                      placeholder="We cover KM-04 scheduling. Have your Learner Guide open and join with the account the academy has for you."><?= e((string) ($editing['body'] ?? '')) ?></textarea>
          </div>
          <div class="field">
            <label for="nt-link">Google Classroom or Meet link (optional)</label>
            <input id="nt-link" type="url" name="link_url" maxlength="500" placeholder="https://classroom.google.com/…"
                   value="<?= e((string) ($editing['link_url'] ?? '')) ?>">
            <p class="field-hint">Must start with https:// and be a Google address.</p>
          </div>
          <div class="field">
            <label for="nt-label">Button text</label>
            <input id="nt-label" type="text" name="link_label" maxlength="<?= NOTICE_LABEL_MAX ?>" placeholder="Join the class"
                   value="<?= e((string) ($editing['link_label'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="nt-exp">Hide it after (optional)</label>
            <input id="nt-exp" type="date" name="expires_on" min="<?= e($today) ?>"
                   value="<?= e((string) ($editing['expires_on'] ?? '')) ?>">
            <p class="field-hint">Set the date of the class, and the notice goes quiet by itself afterwards.</p>
          </div>
          <label class="reg-radio"><input type="checkbox" name="pinned" value="1"
            <?= !empty($editing['pinned']) ? 'checked' : '' ?>> Pin to the top of the board</label>

          <div class="poe-form-act">
            <button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Post notice' ?></button>
            <?php if ($editing): ?><a class="btn btn-ghost" href="admin-notices">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>

      <h3 class="my-h">On the board</h3>
      <?php if (!$items): ?>
        <div class="adm-empty"><strong>No notices yet</strong><p>The first one you post will appear here.</p></div>
      <?php else: ?>
        <div class="adm-scroll">
        <table class="adm-table">
          <thead><tr><th>Notice</th><th>For</th><th>Posted</th><th>Until</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($items as $n): ?>
            <?php $gone = !empty($n['expires_on']) && (string) $n['expires_on'] < $today; ?>
            <tr<?= $gone ? ' class="adm-off"' : '' ?>>
              <td><strong><?= e((string) $n['title']) ?></strong>
                <?php if (!empty($n['pinned'])): ?> <span class="nb-pin">Pinned</span><?php endif; ?>
                <?php if (!empty($n['link_url'])): ?><span class="adm-sub">with a link</span><?php endif; ?></td>
              <td><?= e(notice_audience($n)) ?></td>
              <td><?= e(notice_when((string) $n['created_at'])) ?></td>
              <td><?= !empty($n['expires_on']) ? e((string) $n['expires_on']) . ($gone ? ' (hidden)' : '') : 'Until removed' ?></td>
              <td>
                <?php if (notice_may_edit($n, $me)): ?>
                  <a class="btn btn-ghost" href="<?= e('admin-notices?edit=' . (int) $n['id']) ?>">Edit</a>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Remove this notice for everybody?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="do" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                    <button class="btn btn-ghost" type="submit">Remove</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>

<?php chrome_footer('slim'); ?>
</body>
</html>
