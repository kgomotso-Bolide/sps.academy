<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';

/**
 * Where to go after signing in.
 *
 * Only a path on this site is accepted. A "next" parameter that is allowed to
 * be a full URL turns the sign-in page into an open redirect — somebody sends
 * a staff member a link to our real login page, they sign in successfully, and
 * are handed to a copy of it somewhere else. The leading "//" check matters as
 * much as the "/" one: //evil.example is a protocol-relative URL, not a path.
 */
function safe_next(?string $raw, string $fallback): string
{
    $raw = (string) $raw;
    if ($raw === '' || $raw[0] !== '/' || str_starts_with($raw, '//')) return $fallback;
    return $raw;
}

$user = current_user();
if ($user !== null) {
    redirect($user['role'] === 'admin' ? 'admin' : './');
}

$error = '';
$email = '';

if (is_post()) {
    $email = post_str('email', 190);

    if (!csrf_valid()) {
        $error = 'This page had been open a while and the form expired. Please try again.';
    } else {
        [$ok, $message] = auth_attempt($email, (string) ($_POST['password'] ?? ''));
        if ($ok) {
            csrf_rotate();
            $u = current_user();
            redirect(safe_next($_GET['next'] ?? null, $u['role'] === 'admin' ? 'admin' : './'));
        }
        $error = $message;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — SPS Academy</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<nav id="nav">
  <div class="nav-inner">
    <a href="./" class="brand"><img src="sps-dark-logo.svg" alt="SPS — Sustainable Power Solutions"></a>
    <div class="nav-links" id="navLinks">
      <a href="./" data-nav="home">Home</a>
      <a href="courses" data-nav="courses">Courses</a>
      <a href="contact" data-nav="contact">Contact</a>
    </div>
  </div>
</nav>

<section class="section-soft page-top">
  <div class="wrap">
    <div class="auth-card">
      <span class="eyebrow">SPS Academy</span>
      <h2>Sign in</h2>
      <p class="auth-lede">For academy staff and enrolled learners. If you are registering
        for a course for the first time, you do not need an account —
        <a href="contact">use the registration form</a>.</p>

      <form class="form" method="POST" novalidate>
        <?= csrf_field() ?>
        <?php if ($error !== ''): ?>
          <p class="form-err" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="field">
          <label for="l-email">Email address</label>
          <input id="l-email" type="email" name="email" value="<?= e($email) ?>"
                 autocomplete="username" required autofocus>
        </div>
        <div class="field">
          <label for="l-pass">Password</label>
          <input id="l-pass" type="password" name="password"
                 autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Sign in</button>
      </form>

      <p class="auth-foot">Forgotten your password? There is no self-service reset yet —
        email <a href="mailto:kgomotso@centenarynetworks.com">kgomotso@centenarynetworks.com</a>
        and it will be reset for you.</p>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <p class="disclaimer">SPS Academy is the in-house training academy of SPS — Sustainable Power Solutions. SPS Academy operates in association with Centenary Networks. © 2026 SPS — Sustainable Power Solutions. All rights reserved.</p>
  </div>
</footer>
<script src="site.js"></script>
</body></html>
