/* ---- Where our learners are now -----------------------------------------
   The graduates page. Everything it shows comes from the PEOPLE list below —
   there is no other place to edit, and no back end behind this page.

   ADDING SOMEONE
   --------------
   Copy one of the commented-out examples, fill it in, save, done. Fields:

     name      Their name as they want it written.
     kind      'graduate' or 'intern'. Only changes the little label.
     course    What they did with Centenary. Free text.
     year      When they finished. A string, so "2024" or "2023–24" both work.
     now       Where they are today — one sentence, in plain words.
                 "Site supervisor at Blue Falcon Energy"
                 "Founded Motsepe Electrical, now employing four people"
     photo     A file in images/, e.g. 'images/grad-thandi.jpg'. Optional —
               leave it out and the card shows their initials instead, which
               looks deliberate rather than broken.
     consent   MUST be true or the entry is not rendered. See below.

   THE consent FIELD IS NOT A FORMALITY
   ------------------------------------
   A name, a photograph and an employer together are personal information under
   POPIA, and this page is public — Google will index it, and it will outlive
   the person's interest in being on it. So we do not publish anyone who has not
   said yes.

   Set consent:true only once that person has actually agreed, in writing, to
   their name, photo and current employer appearing on a public web page. An
   email saying "yes, happy for you to use it" is enough; a verbal "sure" during
   a call is not, because in a year nobody will remember it.

   Anyone who asks to come off comes off the same day. Delete their entry — do
   not merely set consent:false, or their details stay in the page source for
   anyone who looks.

   WHEN THE LIST IS EMPTY
   ----------------------
   Which it is today, on purpose: the first Project Manager intake starts on
   10 September 2026 and Centenary's earlier graduates have not been asked yet.
   An empty list renders an honest "we are collecting these" panel rather than
   invented people. Do not seed it with examples to make the page look full. */
(function () {
  'use strict';

  var PEOPLE = [

    /* ---- delete this comment block as you add real entries ----
    {
      name: 'Thandi Mokoena',
      kind: 'graduate',
      course: 'Occupational Certificate: Project Manager',
      year: '2024',
      now: 'Project coordinator at Blue Falcon Energy',
      photo: 'images/grad-thandi.jpg',
      consent: true
    },
    {
      name: 'Sipho Dlamini',
      kind: 'intern',
      course: 'Network operations internship',
      year: '2023',
      now: 'Founded Dlamini Fibre Services, employing four people',
      consent: true
    },
    ---- */

  ];

  var host = document.getElementById('grads');
  if (!host) return;

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  /* Initials, for anyone without a photograph. Two letters at most: three
     initials in a 76px circle is a smudge. */
  function initials(name) {
    return String(name).trim().split(/\s+/).slice(0, 2)
      .map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
  }

  function card(p) {
    var face = p.photo
      ? '<img class="grad-photo" loading="lazy" src="' + esc(p.photo) + '" alt="' + esc(p.name) + '">'
      : '<span class="grad-initials" aria-hidden="true">' + esc(initials(p.name)) + '</span>';

    return '<figure class="grad">' +
      face +
      '<figcaption>' +
        '<span class="grad-kind">' + (p.kind === 'intern' ? 'Intern' : 'Graduate') +
          (p.year ? ' · ' + esc(p.year) : '') + '</span>' +
        '<strong>' + esc(p.name) + '</strong>' +
        (p.course ? '<span class="grad-course">' + esc(p.course) + '</span>' : '') +
        (p.now ? '<p>' + esc(p.now) + '</p>' : '') +
      '</figcaption>' +
    '</figure>';
  }

  var shown = PEOPLE.filter(function (p) { return p.consent === true && p.name; });

  if (!shown.length) {
    /* Deliberately not apologetic and not a fake "coming soon" teaser. It says
       what is true and what somebody reading it can do about it. */
    host.innerHTML =
      '<div class="corp" style="margin-top:0">' +
        '<div>' +
          '<span class="tag-new">In progress</span>' +
          '<h3>We are still collecting these</h3>' +
          '<p>Centenary Networks has trained and placed people since long before this ' +
            'academy existed. We are going back to them one by one and asking permission ' +
            'to put their name and photograph on a public page, which is not something we ' +
            'will do without being asked first.</p>' +
          '<p style="margin-top:10px">Came through Centenary, and happy to be listed? ' +
            'We would like to hear from you.</p>' +
        '</div>' +
        '<a href="contact" class="btn btn-primary">Get in touch</a>' +
      '</div>';
    return;
  }

  host.innerHTML = '<div class="grads">' + shown.map(card).join('') + '</div>';
})();
