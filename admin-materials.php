<?php
declare(strict_types=1);

/* Where an administrator pastes the links to the course material.
 *
 * The material itself lives in Centenary's Google Workspace. This page records
 * which Drive or SharePoint address belongs to which module, and materials.php
 * hands those addresses out to learners who are enrolled — logging each one.
 *
 * WHY THE MODULE LIST IS NOT IN THIS FILE
 *
 * The eleven modules, their codes and their titles live in pm-modules.js, which
 * is the registered curriculum and the single source of truth for every page
 * that shows it. Writing them out again here in PHP would create a second copy
 * that disagrees with the first the day a module changes — the same argument
 * that kept percentages off the Accounts page. So this page loads that file and
 * builds its rows from window.PM_MODULES in the browser. PHP validates the
 * shape of what comes back and nothing more.
 *
 * The cost of that choice is honest: with JavaScript off, this page shows an
 * explanation and no form. It is a staff-only page used a handful of times per
 * intake, and the alternative is a curriculum that drifts.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/materials.php';
require __DIR__ . '/lib/chrome.php';

$me = require_admin();

/* Only courses whose curriculum this platform actually carries. The others are
   real courses, but nothing here knows what their modules are. */
$courses = array_filter(learner_catalogue(),
                        fn(array $c): bool => (bool) ($c['tracked'] ?? false));

$course = (string) ($_GET['course'] ?? array_key_first($courses));
if (!isset($courses[$course])) $course = (string) array_key_first($courses);

$notice = '';
$errors = [];

if (is_post()) {
    if (!csrf_valid()) {
        $errors[] = 'That form had expired — nothing was saved. Please try again.';
    } else {
        $posted = post_str('course', 60);
        if (!isset($courses[$posted])) {
            $errors[] = 'That is not a course this page can manage.';
        } else {
            $course = $posted;

            /* The form posts links[MODULE][kind]. Everything is validated here;
               nothing trusts that the browser sent well-formed module codes. */
            $links  = $_POST['links'] ?? [];
            $counts = ['added' => 0, 'replaced' => 0, 'removed' => 0, 'unchanged' => 0];

            if (is_array($links)) {
                foreach ($links as $module => $kinds) {
                    if (!is_string($module) || !is_array($kinds)) continue;
                    if (!learner_valid_code($module, 20)) {
                        $errors[] = 'Ignored a module code that did not look right.';
                        continue;
                    }
                    foreach ($kinds as $kind => $url) {
                        if (!is_string($kind) || !is_string($url)) continue;
                        if (!materials_kind_valid($kind)) continue;

                        $url = trim($url);
                        if ($url !== '' && !materials_url_allowed($url)) {
                            $errors[] = $module . ' ' . $kind . ' — ' . materials_url_problem($url);
                            continue;
                        }
                        $what = materials_set($course, $module, $kind, $url, (int) $me['id']);
                        $counts[$what] = ($counts[$what] ?? 0) + 1;
                    }
                }
            }

            $changed = $counts['added'] + $counts['replaced'] + $counts['removed'];
            if ($changed > 0) {
                $bits = [];
                if ($counts['added'])    $bits[] = $counts['added'] . ' added';
                if ($counts['replaced']) $bits[] = $counts['replaced'] . ' replaced';
                if ($counts['removed'])  $bits[] = $counts['removed'] . ' removed';
                $notice = 'Saved — ' . implode(', ', $bits) . '.';
            } elseif (!$errors) {
                $notice = 'Nothing had changed.';
            }
        }
        csrf_rotate();
    }
}

$existing = db_optional(fn() => materials_for_course($course), []);
$filled   = 0;
foreach ($existing as $kinds) $filled += count($kinds);

/* Only the URLs go to the browser — this page is administrator-only, and the
   administrator is the person who pasted them in the first place. */
$forJs = [];
foreach ($existing as $module => $kinds) {
    foreach ($kinds as $kind => $row) $forJs[$module][$kind] = (string) $row['url'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Course material — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('admin', ['active' => 'admin-materials', 'name' => $me['first_name']]); ?>

<section class="section-soft page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Academy administration</span>
        <h2>Course material</h2>
      </div>
    </div>

    <?php if (db_schema_incomplete()): ?>
      <p class="form-err" role="alert"><?= e(db_schema_notice()) ?></p>
    <?php endif; ?>
    <?php if ($notice !== ''): ?><p class="adm-notice" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php foreach ($errors as $err): ?><p class="form-err" role="alert"><?= e($err) ?></p><?php endforeach; ?>

    <div class="mat-intro">
      <p><strong>The academy does not hold the files.</strong> Guides, workbooks and recordings live
        in Centenary&rsquo;s Google Workspace, and this page records which address belongs to which
        module. A learner who is signed in and enrolled sees the material on the module page; nobody
        else is given the address, and every open is logged against the learner who opened it.</p>

      <p class="mat-warn"><strong>Set the sharing on the file before you paste the link here.</strong>
        In Drive, use <em>anyone with the link &mdash; Viewer</em>. Until you do, learners will click
        through to a request-access screen. And be clear about what that setting means: the link
        <em>is</em> the protection &mdash; anyone who has it can open the file. So this page is for
        learner guides, workbooks and recordings only, and
        <strong>never for summative assessments, marking memos or facilitator guides</strong>.</p>
    </div>

    <?php if (count($courses) > 1): ?>
      <form class="adm-search" method="GET">
        <select name="course" onchange="this.form.submit()">
          <?php foreach ($courses as $slug => $c): ?>
            <option value="<?= e($slug) ?>"<?= $slug === $course ? ' selected' : '' ?>>
              <?= e((string) ($c['title'] ?? $slug)) ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-primary">Show</button></noscript>
      </form>
    <?php endif; ?>

    <p class="mat-count"><strong><?= (int) $filled ?></strong> link<?= $filled === 1 ? '' : 's' ?>
      saved for <?= e((string) ($courses[$course]['title'] ?? $course)) ?>.</p>

    <form method="POST" id="mat-form">
      <?= csrf_field() ?>
      <input type="hidden" name="course" value="<?= e($course) ?>">
      <div id="mat-rows">
        <noscript><p class="adm-empty">This page needs JavaScript, because it reads the module list
          from the curriculum file rather than keeping a second copy of it.</p></noscript>
      </div>
      <div class="mat-save"><button type="submit" class="btn btn-primary">Save the links</button></div>
    </form>

  </div>
</section>

<script src="<?= e(asset('pm-modules.js')) ?>"></script>
<script>
(function () {
  var MODS  = window.PM_MODULES || [];
  var HAVE  = <?= json_encode($forJs, JSON_UNESCAPED_SLASHES) ?>;
  var KINDS = [
    ['guide',    'Learner guide', 'The notes this module is assessed on'],
    ['workbook', 'Workbook',      'Activities and self-assessments'],
    ['video',    'Recording',     'A facilitator session, if there is one']
  ];
  var rows = document.getElementById('mat-rows');
  if (!rows || !MODS.length) return;

  var ESCMAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) { return ESCMAP[c]; });
  }

  rows.innerHTML = MODS.map(function (m) {
    var have = HAVE[m.id] || {};
    var n    = Object.keys(have).length;
    var fields = KINDS.map(function (k) {
      var v = have[k[0]] || '';
      return '<div class="field">' +
        '<label for="' + esc(m.id + '-' + k[0]) + '">' + esc(k[1]) +
          (v ? ' <span class="mat-on">saved</span>' : '') + '</label>' +
        '<input id="' + esc(m.id + '-' + k[0]) + '" type="url" ' +
          'name="links[' + esc(m.id) + '][' + esc(k[0]) + ']" ' +
          'value="' + esc(v) + '" placeholder="' + esc(k[2]) + '">' +
        '</div>';
    }).join('');
    /* Modules with nothing in them start open, because those are the ones
       somebody came here to fill in. */
    return '<details class="mat-mod"' + (n ? '' : ' open') + '>' +
      '<summary><span class="mat-code">' + esc(m.id) + '</span> ' + esc(m.title) +
        '<span class="mat-have' + (n ? ' on' : '') + '">' + n + ' of 3</span></summary>' +
      '<div class="mat-fields">' + fields + '</div></details>';
  }).join('');

  /* Say so before the form is sent rather than after a round trip. The server
     checks the same thing again — this is a courtesy, not the guard. */
  var ALLOWED = /^https:\/\/([a-z0-9-]+\.)*(drive\.google\.com|docs\.google\.com|youtube\.com|youtu\.be|onedrive\.live\.com|1drv\.ms|sharepoint\.com)\//i;

  document.getElementById('mat-form').addEventListener('submit', function (e) {
    var bad = [].filter.call(this.querySelectorAll('input[type=url]'), function (i) {
      var v = i.value.trim();
      return v !== '' && !ALLOWED.test(v);
    });
    if (bad.length) {
      e.preventDefault();
      bad.forEach(function (i) { i.classList.add('has-err'); });
      bad[0].closest('details').open = true;
      bad[0].focus();
      alert(bad.length + (bad.length > 1 ? ' links do' : ' link does') +
            ' not point at Google Drive, SharePoint, OneDrive or YouTube, or does not start ' +
            'with https://. Nothing has been saved — the fields are marked.');
    }
  });
})();
</script>
<script src="<?= e(asset('site.js')) ?>"></script>
</body></html>
