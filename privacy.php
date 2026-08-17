<?php
/* The privacy notice.
 *
 * DRAFT — needs Kgomotso's sign-off, and ideally a legal read, before the site
 * goes live on the subdomain. It is written from what is actually true of the
 * system as built rather than from a template, so where it is wrong it is wrong
 * about a fact we can check, not about a paragraph nobody read.
 *
 * Three things in it are genuinely unknown and are marked TO CONFIRM on the
 * page itself rather than quietly guessed:
 *   - who each company has appointed as its Information Officer;
 *   - whether Centenary's Information Officer is registered with the
 *     Information Regulator;
 *   - the retention period the QCTO requires for learner records, which drives
 *     everything the enrolment tables will keep.
 *
 * The version string comes from the config so that the notice, and the
 * consents recorded against it, can never disagree about which wording was on
 * screen when somebody ticked the box.
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';

$version = (string) (app_config('policy_version') ?? 'unversioned');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Notice — SPS Academy</title>
<meta name="description" content="How SPS Academy and Centenary Networks handle your personal information, and what you can ask us to do with it.">
<meta name="robots" content="noindex">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<nav id="nav">
  <div class="nav-inner">
    <a href="./" class="brand"><img src="sps-dark-logo.svg" alt="SPS — Sustainable Power Solutions"></a>
    <div class="nav-links" id="navLinks">
      <a href="./" data-nav="home">Home</a>
      <a href="ai-in-action" data-nav="ai">AI in Action</a>
      <a href="about" data-nav="about">About</a>
      <a href="courses" data-nav="courses">Courses</a>
      <a href="skills-gap" data-nav="skills">Skills Gap</a>
      <a href="contact" data-nav="contact">Contact</a>
      <a href="profile" data-nav="profile" class="nav-profile" title="Your profile"><span class="np-av" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><span class="np-label">Profile</span></a>
      <a href="contact" class="nav-cta">Upskill Yourself</a>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:26px;height:26px;display:block"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
  </div>
</nav>

<section class="section-dark page-top">
  <div class="wrap">
    <div class="sec-head sec-head-wide">
      <span class="eyebrow">Privacy</span>
      <h2>How we handle your information</h2>
      <p class="lede-h">This notice explains what SPS Academy collects when you register for a course, why we need it, who else sees it, and what you can tell us to do with it. It is written to be read, not to be scrolled past.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap legal">

    <p class="legal-meta">Version <?= e($version) ?> · This notice applies to the SPS Academy website and the course registrations made through it.</p>

    <div class="legal-draft">
      <strong>Draft for approval.</strong> This notice is accurate about how the system works,
      but it has not yet been approved by SPS or by Centenary Networks, and the three items
      marked <em>to confirm</em> below are outstanding. It should not be treated as final until
      that is done.
    </div>

    <h3>Who is responsible for your information</h3>
    <p>Two organisations are involved, and under the Protection of Personal Information Act 4 of 2013 (POPIA) they have different roles.</p>
    <ul>
      <li><strong>SPS — Sustainable Power Solutions</strong> is your employer and the <em>responsible party</em>. It decides that this training happens and why.</li>
      <li><strong>Centenary Networks (Pty) Ltd</strong> runs the academy and this website on SPS's behalf, which makes it an <em>operator</em>. Centenary is also an accredited Skills Development Provider, and in that role it keeps the learner records the Quality Council for Trades and Occupations (QCTO) requires it to keep.</li>
    </ul>
    <p class="legal-tbc"><strong>To confirm:</strong> the name and contact details of the Information Officer appointed by SPS, and confirmation that Centenary Networks' Information Officer is registered with the Information Regulator.</p>

    <h3>What we collect</h3>
    <p>When you register your interest in a course, we collect only what is on the form: your name, work email address, and — if you choose to give them — your contact number, employee number, department, line manager's name, the course you are interested in, and anything you write in the message box.</p>
    <p>We also record the date and time, and a one-way scrambled version of your internet address. We keep the scrambled version rather than the address itself so that we can spot a flood of automated submissions without holding information that identifies your connection.</p>
    <p>We do <strong>not</strong> collect your ID number at this stage. It is needed later to register you with the QCTO for an accredited qualification, and we will ask for it then, separately, and tell you why.</p>

    <h3>Why we need it, and on what basis</h3>
    <p>We use it to contact you about the course, to confirm your place on an intake, and — if you go on to enrol in an accredited qualification — to register and assess you for it. We rely on your consent, which you give by ticking the box on the registration form, and on the fact that this processing is necessary to deliver training your employer has arranged.</p>
    <p>We do not use your details for marketing, we do not sell them, and we do not use them for anything other than the training you registered for.</p>

    <h3>Who else sees it</h3>
    <ul>
      <li><strong>SPS HR</strong>, who confirm your place and arrange cover with your manager.</li>
      <li><strong>Centenary Networks</strong>, as the training provider.</li>
      <li>For accredited qualifications only: the <strong>QCTO</strong> and the relevant SETA, because a qualification cannot be registered against your name without them being told your name. This is a legal requirement, not a choice either of us makes.</li>
    </ul>

    <h3>Where it is kept</h3>
    <p>On a server in <strong>Johannesburg, South Africa</strong>. Your information is not transferred out of the country. That is a deliberate choice: it means the protections of POPIA apply to it directly, without relying on an assessment of another country's laws.</p>
    <p>The connection to this site is encrypted, passwords are stored in a form that cannot be reversed, and access to the records is logged.</p>

    <h3>How long we keep it</h3>
    <p>If you register your interest and do not go on to enrol, we delete your registration <strong>twelve months</strong> after you sent it. That is long enough to cover the next intake and the one after it.</p>
    <p>If you do enrol in an accredited qualification, your learner record has to be kept for as long as the QCTO requires — a qualification that cannot be evidenced is a qualification you cannot prove you hold. That period is longer, and it is not something we can shorten at your request.</p>
    <p class="legal-tbc"><strong>To confirm:</strong> the exact retention period required by the QCTO for learner and assessment records.</p>

    <h3>What you can ask us to do</h3>
    <p>Under POPIA you may ask us to:</p>
    <ul>
      <li>tell you what we hold about you, and give you a copy;</li>
      <li>correct anything that is wrong;</li>
      <li>delete it, where we are not obliged to keep it;</li>
      <li>stop using it, where we are relying on your consent — you can withdraw that consent at any time, though we may then be unable to place you on a course.</li>
    </ul>
    <p>Ask by emailing <a href="mailto:kgomotso@centenarynetworks.com">kgomotso@centenarynetworks.com</a>. We will respond within a reasonable time, and we will not charge you for a first request.</p>

    <h3>If you are not satisfied</h3>
    <p>Tell us first — most things are a misunderstanding and are quicker to fix directly. If that does not resolve it, you have the right to complain to the Information Regulator of South Africa, at <a href="https://inforegulator.org.za" target="_blank" rel="noopener">inforegulator.org.za</a>.</p>

    <h3>Changes to this notice</h3>
    <p>If we change how we handle your information, we will change this notice and give it a new version number. Every consent we hold is recorded against the version that was on screen at the time, so you can always find out exactly what you agreed to.</p>

  </div>
</section>

<footer>
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand"><img src="sps-dark-logo.svg" alt="SPS — Sustainable Power Solutions"></div>
      <div class="foot-nav">
        <a href="./">Home</a>
        <a href="ai-in-action">AI in Action</a>
        <a href="about">About</a>
        <a href="courses">Courses</a>
        <a href="skills-gap">Skills Gap</a>
        <a href="rpl">RPL</a>
        <a href="profile">Profile</a>
        <a href="contact">Contact</a>
        <a href="privacy">Privacy</a>
      </div>
    </div>
    <p class="disclaimer">SPS Academy is the in-house training academy of SPS — Sustainable Power Solutions, for SPS employees. SPS Academy operates in association with Centenary Networks. © 2026 SPS — Sustainable Power Solutions. All rights reserved.</p>
  </div>
</footer>
<script src="site.js"></script>
</body></html>
