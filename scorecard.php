<?php
declare(strict_types=1);

/* The B-BBEE Skills Development scorecard — HR only.
 *
 * WHY THIS IS A PHP PAGE AND NOT A STATIC ONE.
 * It shipped on 25 Aug 2026 as a public scorecard.html. Kgomotso came back the
 * same day: HR may read it, learners may not. So it moved behind require_admin()
 * — on this system HR *are* the administrators, the same people who work the
 * registrations list.
 *
 * A GATE ON THE PAGE IS NOT A GATE ON THE DATA. The figures used to live in a
 * public /scorecard.js, which anyone could fetch whether or not they could open
 * the page. Gating the page and leaving that file where it was would have been
 * theatre. It is now lib/scorecard.js — .htaccess 404s the whole of lib/ — and
 * it is inlined into this page below, after the gate has run. Do not move it
 * back into the web root, and do not add a <script src> to it.
 *
 * THE ORPHANS ON THE SERVER. The public version was deployed, and the deploy
 * mirror runs with delete_removed off, so scorecard.html and scorecard.js may
 * still be sitting on the server even though they are gone from the repository.
 * .htaccess handles both: it routes /scorecard to this file explicitly rather
 * than letting the extensionless rewrite find the stale .html, and it 404s the
 * two orphan filenames outright. Read that block before changing either.
 *
 * EVERY VIEW IS AUDITED, as on the other administration pages. Under POPIA the
 * question after an incident is not "was there a login" but "what was read",
 * and this page holds the company's verified B-BBEE position.
 *
 * WHAT IS NOT HERE, DELIBERATELY. The verification report itself. It lives in
 * private/ — 404'd, gitignored, excluded from the deploy — because it carries
 * the company registration number, VAT number, physical address and the
 * ownership percentages. None of those appear on this page and none should.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/csrf.php';   // chrome_signout() builds a form; without this the page is a 500
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/chrome.php';

$me = require_admin();

audit('scorecard.viewed', 'page', null, 'B-BBEE Skills Development, certificate SPSGEN069');

$data = __DIR__ . '/lib/scorecard.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>B-BBEE Skills Development — <?= e(brand('academy')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= e(asset('styles.css')) ?>">
</head>
<body>
<?php chrome_nav('admin', ['active' => 'scorecard', 'name' => $me['first_name']]); ?>

<section class="section page-top">
  <div class="wrap">

    <div class="adm-head">
      <div>
        <span class="eyebrow">Academy administration</span>
        <h2>B-BBEE Skills Development</h2>
      </div>
    </div>

    <div class="sec-head reveal sec-head-wide">
      <p>Training is not only something SPS gives its people &mdash; it is a measured element of the company&rsquo;s B-BBEE scorecard, and it is the element the academy sits inside. Below is the <strong>Skills Development element</strong> as it came back from verification, with every indicator explained and linked to the part of the academy that feeds it.</p>
      <p class="pg-caveat">These figures are the <strong>verification report&rsquo;s</strong>, transcribed. The academy does not calculate B-BBEE and none of the numbers here are its own working. The report itself is not on this site &mdash; it carries company registration, VAT and ownership detail that has no business on a web page. Read the notes at the foot of this page before quoting anything off it.</p>
    </div>

    <!-- Rendered by lib/scorecard.js, inlined below. Read the notes at the top
         of that file first — in particular which figures are the report's and
         which are arithmetic on top of them, and why a course is never labelled
         with a Learning Programme Matrix category. -->
    <div id="scorecard" class="reveal"></div>

    <div class="sc-note reveal">
      <h3>Reading this without getting it wrong</h3>
      <dl>
        <dt>HR only, and that is a decision rather than an oversight.</dt>
        <dd>Kgomotso, 25 August 2026: this page is for HR, not for learners. It is behind the administration login for that reason. If someone needs a figure off it, give them the figure &mdash; do not send them the link, because it will not open for them.</dd>

        <dt>One element is broken down here, not five.</dt>
        <dd>B-BBEE is scored across five elements and the Level 2 comes from all of them together. Skills Development is the one the academy sits inside, so it is the only one this page opens up. The other four appear as totals and nothing more.</dd>

        <dt>It is a closed period, and it expires.</dt>
        <dd>Everything here measures <strong>01 March 2025 to 28 February 2026</strong>, verified 07 July 2026, on the <strong>Amended Construction Sector Codes</strong>. The certificate expires <strong>02 August 2027</strong>. Targets differ between sector codes, so none of these numbers transfer to a company measured on another one.</dd>

        <dt>The totals are added up, not re-scored.</dt>
        <dd>Two things on this page are calculated rather than transcribed: the twelve scores added together, and how far each line fell short of its own weighting. Both were checked back against the report &mdash; the scores sum to 24.63 exactly, and every line follows the same formula to the cent. That is arithmetic on the report&rsquo;s figures, not a second opinion about them.</dd>

        <dt>A course&rsquo;s Learning Programme Matrix category is the practitioner&rsquo;s call.</dt>
        <dd>Categories A to G decide what a programme is worth on two of these indicators, and assigning them is the B-BBEE practitioner&rsquo;s job. This page says what <em>kind</em> of activity counts on a line; it never claims a particular course is a Category B or C.</dd>

        <dt>Enrolling is not a B-BBEE transaction.</dt>
        <dd>Courses are fully funded because SPS decided to fund them. That the spend also counts on this element is true, and it is why this page exists &mdash; but it is not the reason to put anyone on a course, and nobody should be enrolled to move a number.</dd>
      </dl>
    </div>

    <p class="adm-foot">Every view of this page is logged. The figures are transcribed from the
      Final BEE Verification Report issued to SPS RSA (Pty) Ltd on 03 August 2026 (certificate
      SPSGEN069, measured 01 March 2025 &ndash; 28 February 2026 against the Amended Construction
      Sector Codes of Good Practice, expiring 02 August 2027). This page is a summary for internal
      use and is not itself a certificate, a verification, or advice &mdash; the certificate governs,
      and it is the document to ask for.</p>

  </div>
</section>
<script src="<?= e(asset('site.js')) ?>"></script>
<script>
<?php readfile($data); ?>
</script>
</body></html>
