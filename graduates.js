/* ---- Where our learners are now -----------------------------------------
   The graduates page. Everything it shows comes from the two lists below —
   there is no other place to edit, and no back end behind this page.

   REVIEW MODE — READ THIS FIRST
   ----------------------------
   REVIEW is true today. The page is showing the 2022 cohort so Kgomotso can
   see the real thing on the real site and say what he wants changed, and it
   carries a visible banner saying exactly that. Nobody in that list has been
   asked yet.

   Turning REVIEW off is what makes this page publishable: the banner goes, and
   only people with consent:true are rendered. So the sequence is

       1. Kgomotso approves the layout          <- where we are
       2. Every person listed gives permission, in writing
       3. Set consent:true on the ones who did
       4. REVIEW = false
       5. Delete the noindex line from graduates.html, and put the words "and
          appear here with their permission" back into its footer disclaimer
          — they were removed because today they would not be true.

   Do not do 4 before 2. The whole point of the flag is that it fails closed:
   if someone flips REVIEW off while the list is still unconsented, the page
   goes empty rather than public.

   ADDING SOMEONE
   --------------
     name      Their name as they want it written.
     role      Their trade or job title. "Electrical Technician".
     cohort    Which COHORTS id they belong to. Unknown or missing puts them
               in a plain grid at the end, which is untidy but not broken.
     kind      'graduate' or 'intern'. Only changes the little label.
     course    What they did with Centenary. Free text.
     year      When they finished. A string, so "2024" or "2023-24" both work.
     now       Where they are today — one sentence, in plain words.
                 "Site supervisor at Blue Falcon Energy"
                 "Founded Motsepe Electrical, now employing four people"
               THIS IS THE FIELD THAT MATTERS. Kgomotso asked for the people
               who got jobs and started companies; a job title alone does not
               answer that. Where `now` is set it replaces the role on the card.
     photo     A file in images/graduates/. Optional — leave it out and the
               card shows their initials, which looks deliberate rather than
               broken. A photo that fails to load falls back to the same
               initials, so a missing file cannot put a broken image on a
               client's site.
     consent   MUST be true to be published. See below.

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

   Anyone who asks to come off comes off the same day. Delete their entry AND
   their photograph from images/graduates/ — do not merely set consent:false,
   or their details stay in the page source for anyone who looks. */
(function () {
  'use strict';

  /* Internal preview. See the note above before changing this. */
  var REVIEW = true;

  /* A cohort is a group who came through together. `photo` and `stats` are
     optional: a cohort with them gets the wide panel, a cohort without gets
     just a heading over its faces. */
  var COHORTS = [
    {
      id: '2021',
      eyebrow: 'First cohort · 2021–2024',
      title: 'Apprenticeship & Mentorship',
      blurb: 'Three years, one intake, and every one of them qualified. They went ' +
             'into the trade on a starting salary of R16 000 a month.',
      photo: 'images/graduates/cohort-2021-dartcom.jpg',
      alt: 'The first Centenary Networks apprenticeship cohort outside Dartcom',
      stats: [
        { n: '100%',    l: 'Pass rate' },
        { n: 'R16k',    l: 'Starting salary' },
        { n: '2021–24', l: 'Apprenticeship' }
      ]
    },
    {
      id: '2022',
      eyebrow: 'Second cohort',
      title: 'Electrical & civil learners'
    }
  ];

  var PEOPLE = [
    /* The 2022 intake. Names and trades are from Centenary's own cohort sheet.
       `now` is deliberately absent on all of them — nobody has told us yet where
       these nine are working today, and that is the one thing this page is for.
       consent:false on every one of them: none has been asked. */
    { name: 'Langelihle Ngidi', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/langelihle-ngidi.jpg', consent: false },
    { name: 'Sandiso Mthiyane', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/sandiso-mthiyane.jpg', consent: false },
    { name: 'Sibusiso Seopela', role: 'Field Technician', cohort: '2022',
      photo: 'images/graduates/sibusiso-seopela.jpg', consent: false },
    { name: 'Tshepo Thobejane', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/tshepo-thobejane.jpg', consent: false },
    { name: 'Koketso Mokgethi', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/koketso-mokgethi.jpg', consent: false },
    { name: 'Thabelo Ndou', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/thabelo-ndou.jpg', consent: false },
    { name: 'Khotso Moloantoa', role: 'Electrical Technician', cohort: '2022',
      photo: 'images/graduates/khotso-moloantoa.jpg', consent: false },
    { name: 'Vuyo Matanga', role: 'Field Technician', cohort: '2022',
      photo: 'images/graduates/vuyo-matanga.jpg', consent: false },
    { name: 'Nandipha Sithole', role: 'Metering Technician', cohort: '2022',
      photo: 'images/graduates/nandipha-sithole.jpg', consent: false }
  ];

  var host = document.getElementById('grads');
  if (!host) return;

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  /* Initials, for anyone without a photograph. Two letters at most: three
     initials in a small circle is a smudge. */
  function initials(name) {
    return String(name).trim().split(/\s+/).slice(0, 2)
      .map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
  }

  /* A photo that 404s must not leave a broken image on a client's site, so the
     img removes itself and hands over to the initials block sitting behind it. */
  function face(p) {
    var ini = '<span class="face-ini" aria-hidden="true">' + esc(initials(p.name)) + '</span>';
    if (!p.photo) return ini;
    return ini + '<img class="face-img" loading="lazy" alt="' + esc(p.name) + '"' +
           ' src="' + esc(p.photo) + '" onerror="this.remove()">';
  }

  function card(p) {
    var line = p.now || p.role || p.course || '';
    return '<figure class="face">' +
      '<span class="face-shot">' + face(p) + '</span>' +
      '<figcaption>' +
        '<strong>' + esc(p.name) + '</strong>' +
        (line ? '<span>' + esc(line) + '</span>' : '') +
        (p.now && p.role ? '<em>' + esc(p.role) + '</em>' : '') +
      '</figcaption>' +
    '</figure>';
  }

  function cohortPanel(c) {
    if (!c.photo && !c.stats) return '';
    var stats = (c.stats || []).map(function (s) {
      return '<div><strong>' + esc(s.n) + '</strong><span>' + esc(s.l) + '</span></div>';
    }).join('');
    return '<div class="cohort"><div class="cohort-top">' +
      (c.photo ? '<img src="' + esc(c.photo) + '" alt="' + esc(c.alt || c.title) + '">' : '') +
      '<div class="cohort-body">' +
        (c.eyebrow ? '<span class="eyebrow">' + esc(c.eyebrow) + '</span>' : '') +
        '<h3>' + esc(c.title) + '</h3>' +
        (c.blurb ? '<p>' + esc(c.blurb) + '</p>' : '') +
        (stats ? '<div class="cohort-stats">' + stats + '</div>' : '') +
      '</div>' +
    '</div></div>';
  }

  var shown = PEOPLE.filter(function (p) {
    return p.name && (REVIEW || p.consent === true);
  });

  /* Nothing to show at all — no consented people and no cohort panel. Deliberately
     not apologetic and not a fake "coming soon" teaser: it says what is true and
     what somebody reading it can do about it. */
  var panels = COHORTS.map(cohortPanel).join('');
  if (!shown.length && !panels) {
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

  var html = '';

  if (REVIEW) {
    /* The static noindex lives in graduates.html — but this file is on the sync
       manifest and that file is not, so a sync would carry nine unconsented
       names to three more public sites and leave the protection behind. Put it
       back from here if it is missing. A tag added by script is weaker than one
       in the source, which is why graduates.html still has its own; this is the
       backstop, not the plan. */
    if (!document.querySelector('meta[name="robots"]')) {
      var m = document.createElement('meta');
      m.name = 'robots';
      m.content = 'noindex, nofollow';
      document.head.appendChild(m);
    }

    html +=
      '<div class="grad-review">' +
        '<strong>Internal preview — not for sharing yet</strong>' +
        '<p>This is how the page will look. The nine people below have not yet been ' +
          'asked whether their name and photograph may appear on a public website, so ' +
          'nothing here is approved for publication. The page is also hidden from ' +
          'search engines until it is.</p>' +
        '<p>What is still needed: permission from each person, and one line each on ' +
          'where they are working now or what they have started.</p>' +
      '</div>';
  }

  var placed = {};
  COHORTS.forEach(function (c) {
    var mine = shown.filter(function (p) { return p.cohort === c.id; });
    mine.forEach(function (p) { placed[p.name] = true; });
    if (!c.photo && !c.stats && !mine.length) return;

    html += cohortPanel(c);
    if (!c.photo && !c.stats) {
      html += '<div class="sec-head grad-sub">' +
        (c.eyebrow ? '<span class="eyebrow">' + esc(c.eyebrow) + '</span>' : '') +
        '<h3>' + esc(c.title) + '</h3></div>';
    }
    if (mine.length) html += '<div class="facegrid">' + mine.map(card).join('') + '</div>';
  });

  /* Anyone whose cohort does not match one above still gets shown, rather than
     silently vanishing because of a typo in a field nobody validates. */
  var rest = shown.filter(function (p) { return !placed[p.name]; });
  if (rest.length) html += '<div class="facegrid">' + rest.map(card).join('') + '</div>';

  host.innerHTML = html;
})();
