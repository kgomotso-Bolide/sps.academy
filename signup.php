<?php
declare(strict_types=1);

/* Self sign-up: create an account, pick courses, and start.
 *
 * Added 10 Nov 2026 on Kgomotso's instruction: "Remove that HR must give
 * access. This step is not necessary. A person to just log in and select
 * modules." Until now the only way in was the registration form, after which
 * an administrator created the account, enrolled it and emailed a link.
 *
 * WHO MAY SIGN UP: anybody (Sibusiso, 10 Nov 2026). Every account made here is
 * an ordinary learner, appears in Admin > Users like any other, and can be
 * disabled there. It can never be staff: the role is hard-coded below, not
 * read from the form.
 *
 * WHAT THEY GET: enrolment on the courses they tick, and only those listed in
 * SIGNUP_COURSES (the ones with modules and material on the platform). The
 * material itself is still served only through materials.php, which checks
 * enrolment on every open, and the assessments are never uploaded at all.
 *
 * The registration form (contact.php) stays for questions and team intakes.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/curriculum.php';
require __DIR__ . '/lib/chrome.php';
/* lib/mail.php and lib/letters.php are NOT loaded here on purpose. With them
   loaded, learner_enrol_user() sends the welcome email inside the request, and
   on 10 Nov 2026 a slow mail server held a sign-up past the gateway's limit:
   the account was made but the person saw "504 Gateway Timeout". The email is
   now sent after the response has gone — see the end of the POST branch. */

app_session_start();

/* Off unless this academy's lib/brand.php says 'self_signup'. SPS only. */
if (!brand_has('self_signup')) {
    redirect('contact');
}

/* The courses a person may put themselves on: see LEARNER_SELF_ENROL_COURSES
   in lib/learner.php, which my.php's "Add a course" box uses too. */
const SIGNUP_COURSES = LEARNER_SELF_ENROL_COURSES;

/* More than this many accounts from one connection in an hour is a script. */
const SIGNUP_RATE_LIMIT = 6;

$want = (string) ($_GET['course'] ?? '');
if (!in_array($want, SIGNUP_COURSES, true)) $want = '';

/* Already signed in: there is nothing to create. Send them to their page, and
   to the course they clicked if they clicked one (my.php offers to add it). */
$already = current_user();
if ($already !== null) {
    redirect($want !== '' ? 'my?add=' . rawurlencode($want) : 'my');
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'employee_no' => '',
        'department' => '', 'courses' => $want !== '' ? [$want] : [], 'consent' => false];

if (is_post()) {
    $old['first_name']  = post_str('first_name', 80);
    $old['last_name']   = post_str('last_name', 80);
    $old['email']       = strtolower(post_str('email', 190));
    $old['employee_no'] = post_str('employee_no', 40);
    $old['department']  = post_str('department', 120);
    $old['consent']     = !empty($_POST['consent']);
    $picked = is_array($_POST['courses'] ?? null) ? $_POST['courses'] : [];
    $old['courses'] = array_values(array_intersect(SIGNUP_COURSES, array_map('strval', $picked)));
    $pass    = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $recent = (int) (db_optional(fn() => db_value(
        "SELECT COUNT(*) FROM audit_log WHERE tenant_id = ? AND action = 'signup.created'
           AND ip_hash = ? AND created_at > ?",
        [tenant_id(), client_ip_hash(), gmdate('Y-m-d H:i:s', time() - 3600)]
    ), 0) ?? 0);

    if (!csrf_valid()) {
        $errors['form'] = 'This page had been open a while and the form expired. Please try again.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        // The hidden field a person cannot see. Only a bot fills it in.
        $errors['form'] = 'That did not go through. Please try again.';
    } elseif ($recent >= SIGNUP_RATE_LIMIT) {
        $errors['form'] = 'Too many accounts have been made from this connection in the last hour. '
                        . 'Please try again later.';
    } else {
        if ($old['first_name'] === '') $errors['first_name'] = 'Please enter your first name.';
        if ($old['last_name'] === '')  $errors['last_name']  = 'Please enter your surname.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That email address does not look right.';
        } elseif (db_one('SELECT id FROM users WHERE tenant_id = ? AND email = ?',
                         [tenant_id(), $old['email']]) !== null) {
            $errors['email'] = 'There is already an account with this email address. '
                             . 'Sign in instead, or reset your password if you have forgotten it.';
        }
        if (mb_strlen($pass) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters. '
                                . 'A few ordinary words together is strong and easy to remember.';
        } elseif ($pass !== $confirm) {
            $errors['confirm'] = 'The two passwords do not match.';
        }
        if ($old['courses'] === []) $errors['courses'] = 'Please choose at least one course.';
        if (!$old['consent'])       $errors['consent'] = 'Please confirm you have read how we handle your information.';
    }

    if ($errors === []) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $uid = db_insert('users', [
                'tenant_id'     => tenant_id(),
                'email'         => $old['email'],
                'password_hash' => auth_hash($pass),
                'first_name'    => $old['first_name'],
                'last_name'     => $old['last_name'],
                'employee_no'   => $old['employee_no'] !== '' ? $old['employee_no'] : null,
                'department'    => $old['department']  !== '' ? $old['department']  : null,
                'role'          => 'learner',      // never from the form
                'status'        => 'active',
                'created_at'    => now(),
            ]);
            db_insert('consents', [
                'tenant_id'       => tenant_id(),
                'registration_id' => null,
                'user_id'         => $uid,
                'purpose'         => 'account_signup',
                'policy_version'  => (string) (app_config('policy_version') ?? 'unversioned'),
                'granted'         => 1,
                'granted_at'      => now(),
                'ip_hash'         => client_ip_hash(),
            ]);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            app_log('SIGNUP FAILED: ' . $ex->getMessage());
            $errors['form'] = 'Your account could not be created just now. Nothing was saved — please try again.';
            $uid = 0;
        }

        if ($uid > 0) {
            audit('signup.created', 'users', $uid, 'self sign-up · ' . implode(', ', $old['courses']), $uid);
            $user = db_one('SELECT * FROM users WHERE id = ? AND tenant_id = ?', [$uid, tenant_id()]);
            auth_sign_in($user);
            csrf_rotate();
            foreach ($old['courses'] as $slug) {
                db_optional(fn() => learner_enrol_user($uid, $slug));
            }

            /* Answer first, email second. The learner is signed in and enrolled
               already; the browser gets its redirect now, and the welcome
               letter is written after the connection is handed back, so a slow
               mail server can never turn a finished sign-up into a timeout. */
            session_write_close();
            header('Location: my?welcome=1', true, 303);
            header('Content-Length: 0');
            header('Connection: close');
            ignore_user_abort(true);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                while (ob_get_level() > 0) ob_end_flush();
                flush();
            }
            try {
                require_once __DIR__ . '/lib/mail.php';
                require_once __DIR__ . '/lib/letters.php';
                @set_time_limit(60);
                foreach ($old['courses'] as $slug) {
                    letter_send_welcome($user, $slug, learner_course_title($slug), null);
                }
            } catch (Throwable $e) {
                app_log('SIGNUP WELCOME LETTER FAILED (user ' . $uid . '): ' . $e->getMessage());
            }
            exit;
        }
    }
}

$err = static fn(string $k): string =>
    isset($errors[$k]) ? '<p class="field-err" role="alert">' . e($errors[$k]) . '</p>' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create your account — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('auth', ['tail' => 'signin']); ?>

<section class="section-soft page-top">
  <div class="wrap">
    <div class="auth-card su-card">
      <span class="eyebrow"><?= e(brand('academy')) ?></span>
      <h2>Create your account</h2>
      <p class="auth-lede">Choose your course, set a password, and start straight away.
        Already have an account? <a href="login">Sign in</a>.</p>

      <form class="form" method="POST" novalidate id="su-form">
        <?= csrf_field() ?>
        <?php if (isset($errors['form'])): ?>
          <p class="form-err" role="alert"><?= e($errors['form']) ?></p>
        <?php endif; ?>

        <fieldset class="su-courses<?= isset($errors['courses']) ? ' bad' : '' ?>">
          <legend>Which course do you want to do?</legend>
          <?php foreach (SIGNUP_COURSES as $slug): $c = learner_catalogue()[$slug] ?? null; if (!$c) continue; ?>
            <label class="su-course">
              <input type="checkbox" name="courses[]" value="<?= e($slug) ?>"<?= in_array($slug, $old['courses'], true) ? ' checked' : '' ?>>
              <span><strong><?= e((string) $c['title']) ?></strong><small><?= e((string) $c['note']) ?></small></span>
            </label>
          <?php endforeach; ?>
          <?= $err('courses') ?>
        </fieldset>

        <div class="two">
          <div class="field"><label for="s-first">First name</label>
            <input id="s-first" type="text" name="first_name" value="<?= e($old['first_name']) ?>" autocomplete="given-name" required>
            <?= $err('first_name') ?></div>
          <div class="field"><label for="s-last">Surname</label>
            <input id="s-last" type="text" name="last_name" value="<?= e($old['last_name']) ?>" autocomplete="family-name" required>
            <?= $err('last_name') ?></div>
        </div>
        <div class="field"><label for="s-email">Email address</label>
          <input id="s-email" type="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
          <?= $err('email') ?></div>
        <div class="two">
          <div class="field"><label for="s-emp">Employee number <span class="opt">(if you have one)</span></label>
            <input id="s-emp" type="text" name="employee_no" value="<?= e($old['employee_no']) ?>" placeholder="<?= e(brand('empno_example')) ?>"></div>
          <div class="field"><label for="s-dept">Department / team <span class="opt">(optional)</span></label>
            <input id="s-dept" type="text" name="department" value="<?= e($old['department']) ?>" placeholder="<?= e(brand('dept_example')) ?>"></div>
        </div>
        <div class="two">
          <div class="field"><label for="s-pass">Choose a password</label>
            <input id="s-pass" type="password" name="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN_LENGTH ?>" required>
            <p class="field-hint">At least <?= PASSWORD_MIN_LENGTH ?> characters.</p>
            <?= $err('password') ?></div>
          <div class="field"><label for="s-conf">Type it again</label>
            <input id="s-conf" type="password" name="confirm" autocomplete="new-password" required>
            <?= $err('confirm') ?></div>
        </div>

        <div class="su-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="field field-consent<?= isset($errors['consent']) ? ' bad' : '' ?>">
          <label class="check">
            <input type="checkbox" name="consent" value="1"<?= $old['consent'] ? ' checked' : '' ?> required>
            <span>I agree that <?= e(brand('company_short')) ?> and Centenary Networks may use these details to run my
              account and record my progress on the courses I choose. I have read the
              <a href="privacy" target="_blank" rel="noopener">privacy notice</a>.</span>
          </label>
          <?= $err('consent') ?>
        </div>

        <p class="form-err" id="su-err" role="alert" hidden></p>
        <button type="submit" class="btn btn-primary" style="width:100%" id="su-go">Create my account and start</button>
      </form>
    </div>
  </div>
</section>

<?php chrome_footer('slim'); ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('password-toggle.js')) ?>"></script>
<script>
/* Checks before the form is sent (10 Nov 2026): the same rules the server
   applies, shown at once. The server still checks everything — this only saves
   a round trip. Then one press only, so a slow answer cannot become two
   accounts or a "this email already exists" for somebody who is already in. */
(function () {
  var f = document.getElementById('su-form'); if (!f) return;
  var box = document.getElementById('su-err'), go = document.getElementById('su-go');
  var MIN = <?= (int) PASSWORD_MIN_LENGTH ?>;
  f.addEventListener('submit', function (e) {
    var v = function (n) { var el = f.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
    var problems = [];
    if (!f.querySelector('input[name="courses[]"]:checked')) problems.push('Choose at least one course.');
    if (!v('first_name')) problems.push('Enter your first name.');
    if (!v('last_name')) problems.push('Enter your surname.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v('email'))) problems.push('Enter a valid email address.');
    var p = f.querySelector('[name="password"]').value, c = f.querySelector('[name="confirm"]').value;
    if (p.length < MIN) problems.push('Your password needs at least ' + MIN + ' characters.');
    else if (p !== c) problems.push('The two passwords do not match.');
    if (!f.querySelector('[name="consent"]').checked) problems.push('Tick the box to confirm you have read the privacy notice.');
    if (problems.length) {
      e.preventDefault();
      box.innerHTML = '<strong>Please fix this first:</strong><br>' + problems.map(function (t) {
        return '· ' + t.replace(/[&<>]/g, function (ch) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ch]; });
      }).join('<br>');
      box.hidden = false;
      box.scrollIntoView({ block: 'center', behavior: 'smooth' });
      return;
    }
    box.hidden = true;
    go.disabled = true;
    go.textContent = 'Creating your account…';
  });
})();
</script>
</body></html>
