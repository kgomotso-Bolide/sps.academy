<?php
declare(strict_types=1);

/* "I have forgotten my password."
 *
 * The whole page is one field and one sentence, and the sentence is the
 * interesting part: it is identical whether the address has an account or not.
 * See the note at the top of lib/reset.php for why that matters more than it
 * looks — the sign-in form goes to real trouble to avoid confirming which
 * addresses exist, and a helpful "no account with that email" here would give
 * it away for free.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/mail.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/reset.php';

// Already signed in? Then this is the wrong page — /my has a change-password box
// that does not need a link in an email at all.
if (current_user() !== null) redirect('my');

$sent  = false;
$error = '';
$email = '';

if (is_post()) {
    $email = post_str('email', 190);

    if (post_str('_honey') !== '') {
        /* Silently indistinguishable from success. A bot that is told it was
           caught simply tries something else. */
        audit('password.reset_honeypot');
        $sent = true;
    } elseif (!csrf_valid()) {
        $error = 'This page had been open a while and the form expired. Please try again.';
    } else {
        reset_request($email);
        csrf_rotate();
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgotten password — SPS Academy</title>
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
      <a href="login">Sign in</a>
    </div>
  </div>
</nav>

<section class="section-soft page-top">
  <div class="wrap">
    <div class="auth-card">
      <span class="eyebrow">SPS Academy</span>

      <?php if ($sent): ?>
        <h2>Check your email</h2>
        <p class="auth-lede"><?= e(reset_generic_message()) ?></p>

        <div class="auth-note">
          <strong>If nothing arrives in a few minutes, look in your junk mail.</strong>
          The academy's mail is still being set up properly with the domain, so messages
          from here can be filed as spam. If it is not there either, email
          <a href="mailto:kgomotso@centenarynetworks.com">kgomotso@centenarynetworks.com</a>
          and a new password will be set for you by hand — that route always works.
        </div>

        <p style="margin-top:20px"><a class="btn btn-ghost" href="login">Back to sign in</a></p>

      <?php else: ?>
        <h2>Forgotten your password</h2>
        <p class="auth-lede">Put in the email address you sign in with and we will send you a
          link to set a new one. The link lasts an hour and works once.</p>

        <form class="form" method="POST" novalidate>
          <?= csrf_field() ?>
          <input type="text" name="_honey" tabindex="-1" autocomplete="off" style="display:none">

          <?php if ($error !== ''): ?>
            <p class="form-err" role="alert"><?= e($error) ?></p>
          <?php endif; ?>

          <div class="field">
            <label for="f-email">Email address</label>
            <input id="f-email" type="email" name="email" value="<?= e($email) ?>"
                   autocomplete="username" required autofocus>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">Send me a link</button>
        </form>

        <p class="auth-foot">Remembered it after all? <a href="login">Sign in</a>.</p>
      <?php endif; ?>
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
