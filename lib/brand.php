<?php
/* Everything that makes this installation SPS rather than Fungi, Equinix or Maziv.
 *
 * THIS IS THE ONLY PER-SITE PHP FILE. Every other .php file in this repository
 * is shared verbatim with the other three academies, and tools/sync-backend.php
 * overwrites them from SPS without asking. It refuses to touch this one.
 *
 * So: if you find yourself wanting to edit a page because "SPS says X and Fungi
 * says Y", the answer is a new key in here and brand('key') in the page. If you
 * edit the page instead, the next sync silently reverts you.
 *
 * Nothing secret belongs here — this file is deployed into the web root along
 * with everything else. Credentials live in ~/private/sps-config.php.
 */

return [

  /* The academy, and the company whose academy it is. Two different names, and
     they are not interchangeable: the academy runs the training, the company
     employs the learners and owns the copyright. */
  'academy'       => 'SPS Academy',
  'company'       => 'SPS — Sustainable Power Solutions',

  /* Used mid-sentence — "for SPS employees", "Fully funded by SPS". The long
     form reads badly there, which is the only reason this key exists. */
  'company_short' => 'SPS',

  /* Relative to the site root. SPS's logo sits at the top level; Fungi's is in
     images/. Both are correct — do not "tidy" one to match the other, because
     the file is referenced from static .html pages this application never
     touches, and moving it breaks those. */
  'logo'          => 'sps-dark-logo.svg',
  'logo_alt'      => 'SPS — Sustainable Power Solutions',

  /* Where registrations and reset notifications go, and what the contact page
     prints. The notification address is ALSO in the server configuration as
     notify_email; this one is the published address on the page. They are the
     same today and need not stay that way. */
  'academy_email'   => 'kgomotso@centenarynetworks.com',

  /* Optional. Fungi publishes a second, company-side address; SPS does not, so
     the line is omitted rather than left blank. */
  'enquiries_email' => '',

  /* PLACEHOLDER — this is not a real number and has never been one. It needs to
     be replaced with SPS's switchboard before anyone is told the site is
     finished, or a learner will dial it. */
  'phone'         => '012 345 6789',
  'phone_href'    => '0123456789',
  'office_hours'  => 'Monday–Friday, 08:00–17:00 SAST',

  /* Placeholder text in the registration form. Each company numbers its staff
     differently and names its teams differently, and a form that suggests
     another company's conventions looks like it was not written for you. */
  'empno_example' => 'e.g. SP1234',
  'dept_example'  => 'e.g. Installations, Sales, Field Ops',

  /* Centenary Networks' accreditation, not the client's. It is identical on all
     four sites because it is one accreditation held by one provider. */
  'accred_no'     => '07-QCTO/SDP180526182035',
  'accred_valid'  => '15 May 2026 – 14 May 2031',

  /* Bumped on any release that changes styles.css or a .js file.
     See asset() in lib/chrome.php for why this is not optional. */
  'asset_version' => '20260819',
];
