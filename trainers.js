/* ---- Meet the trainers ---------------------------------------------------
   The specialists who deliver the skills courses. Everything the page shows
   comes from the TRAINERS list below — there is no other place to edit, and no
   back end behind this page.

   WHY THIS PAGE EXISTS
   --------------------
   Kgomotso, 22 Aug 2026, setting up the procurement course: "You will ask both
   for their CV and or maybe use their linkedin profiles under meet the
   trainers." So the page is her instruction, not an invention, and the two
   people she named are the two entries below.

   These are CENTENARY'S specialists, not any one client's — the same people
   teach on every academy. That is why this file is on the sync manifest in
   tools/sync-backend.php, exactly like graduates.js: one list, four sites, no
   argument about who has agreed to what. trainers.html is NOT synced, because
   like every other .html it carries its own site's chrome.

   REVIEW MODE
   -----------
   Set REVIEW = true to put the page into preview: it renders everyone
   regardless of consent, shows an orange "not for sharing" banner, and injects
   a noindex tag. Turning it off publishes only the people with consent:true, so
   it fails closed — an uncleared entry left behind disappears rather than going
   public. This is the same switch, and the same wiring, as graduates.js.

   IT WAS TRUE while the page was being built and reviewed locally. It went
   FALSE on 24 Aug 2026, the moment these four sites were deployed, and that is
   the whole point of the switch: REVIEW renders everyone regardless of consent,
   which is exactly right on a laptop and exactly wrong on a live client site
   linked from every page's nav. Neither trainer has been asked yet, so with
   REVIEW off the page publishes nobody and says so in plain words.

   Turning it back on is a LOCAL preview action. If you turn it on, do not
   deploy until you have turned it off again.

   ADDING SOMEONE
   --------------
     name      Their name as they want it written.
     role      What they teach, in a few words. "Procurement and sourcing".
     org       Their title and company, if they have one worth naming.
     creds     Qualifications, as a short string. Optional.
     teaches   Course slugs they deliver — each becomes a link to the course.
     bio       Two or three sentences of professional history. Not a CV dump.
     linkedin  Full profile URL. Optional; Kgomotso suggested these as an
               alternative to a CV where somebody would rather not send one.
     photo     A file in images/trainers/. Optional — without it the card shows
               their initials, which looks deliberate rather than broken, and a
               photo that 404s falls back to the same initials.
     pending   true while we are still waiting for their profile. The card says
               so plainly instead of carrying invented copy.
     consent   MUST be true to be published. See below.

   THE consent FIELD IS NOT A FORMALITY
   ------------------------------------
   A name, a photograph and a named employer together are personal information
   under POPIA, and this page is public on four domains. There is a second
   problem on top of the first: listing somebody as "your trainer" asserts a
   commercial relationship. If the engagement is not signed, that assertion is
   not ours to publish, however flattering it is to both sides.

   Set consent:true only once that person has agreed to their name and
   professional history appearing on a public web page, AND the engagement is
   actually agreed.

   WHAT WE DO NOT PUBLISH, EVER
   ----------------------------
   Tarryn's covering email to Kgomotso describes her as a Black female
   professional who is ADHD specially abled. None of that is on this page and
   none of it belongs here. It was written to one person, in confidence, to make
   a case for herself — health information and demographic detail do not become
   publishable because they arrived in an email we were forwarded. If she wants
   any of it on her own profile, she can say so herself.

   The same line applies to the CV she sent: the client savings figures, the
   schooling and the personal section stay off. A profile deck is written to be
   shown. A CV is sent to be read.
   -------------------------------------------------------------------------- */
(function () {

  /* Preview switch. See "REVIEW MODE" in the note above before changing this.
     OFF since 24 Aug 2026, because the four sites went live that day. */
  var REVIEW = false;

  var TRAINERS = [

    /* Procurement. Her Waria Consulting profile deck and CV came to us from
       Kgomotso on 22 Aug 2026. The bio below is drawn from the deck — a
       marketing document, written to be shown — plus the qualifications page of
       the CV, and nothing else.

       consent:false until two things are true: Tarryn has said her name and
       history may go on four public academy sites, and the engagement is
       agreed. Neither has happened yet. */
    {
      name: 'Tarryn Norris',
      role: 'Procurement, sourcing and supplier development',
      org: 'Managing Director, Waria Consulting (Pty) Ltd',
      creds: 'MCIPS · B.Com Business Management & Marketing · MBA (Supply Chain), in progress',
      teaches: [['procurement', 'Procurement Skills']],
      bio: 'Twenty years in procurement, sourcing and operations across financial services ' +
           'and insurance — Hollard, Absa, First National Bank and Standard Bank — covering ' +
           'strategic sourcing, category management, tenders, contracts and supplier ' +
           'relationship management. She founded Waria Consulting in 2017 and now consults ' +
           'on procurement strategy and supplier development. Her own record includes ' +
           'building a black-owned and black-woman-owned supplier base across a bank’s ' +
           'marketing categories, and designing an enterprise development programme for ' +
           'new-entrant suppliers — which is the ground this course covers for people ' +
           'working for service providers.',
      linkedin: '',          // ask her for the URL, or leave it off
      consent: false
    },

    /* Solar and electrical. Kgomotso, 22 Aug 2026: "Then I will share one on
       Solar and electrical / Fiston will be the specialist."

       A first name is all we have. No surname, no company, no CV, no LinkedIn,
       and no course to link to yet — so there is no bio here rather than a
       written-around one. pending:true makes the card say that out loud, which
       is the point: an empty slot on a preview page is a reminder, and an
       invented one is a liability.

       This file is shared by all the academies, so: the course he will teach
       matters most on the SPS academy of the four. SPS is a solar business, and
       a solar and electrical skills course is the most on-brand thing either
       specialist could teach there. It is worth chasing on that basis. */
    {
      name: 'Fiston',
      role: 'Solar and electrical',
      pending: true,
      consent: false
    }

  ];

  var host = document.getElementById('trainers');
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
  function face(t) {
    var ini = '<span class="tr-ini" aria-hidden="true">' + esc(initials(t.name)) + '</span>';
    if (!t.photo) return ini;
    return ini + '<img class="tr-img" loading="lazy" alt="' + esc(t.name) + '"' +
           ' src="' + esc(t.photo) + '" onerror="this.remove()">';
  }

  function card(t) {
    var teaches = (t.teaches || []).map(function (c) {
      return '<a class="tr-course" href="course?c=' + esc(c[0]) + '">' + esc(c[1]) + '</a>';
    }).join('');

    var body = t.pending
      ? '<p class="tr-pending">Profile still to come — we have asked for a CV or a ' +
        'LinkedIn profile.</p>'
      : (t.bio ? '<p class="tr-bio">' + esc(t.bio) + '</p>' : '');

    return '<article class="trainer">' +
      '<span class="tr-shot">' + face(t) + '</span>' +
      '<div class="tr-body">' +
        '<h3>' + esc(t.name) + '</h3>' +
        (t.role ? '<p class="tr-role">' + esc(t.role) + '</p>' : '') +
        (t.org ? '<p class="tr-org">' + esc(t.org) + '</p>' : '') +
        (t.creds ? '<p class="tr-creds">' + esc(t.creds) + '</p>' : '') +
        body +
        (teaches ? '<div class="tr-courses"><span>Teaches</span>' + teaches + '</div>' : '') +
        (t.linkedin
          ? '<a class="tr-link" href="' + esc(t.linkedin) + '" target="_blank" ' +
            'rel="noopener noreferrer">LinkedIn profile</a>'
          : '') +
      '</div>' +
    '</article>';
  }

  var shown = TRAINERS.filter(function (t) {
    return t.name && (REVIEW || t.consent === true);
  });

  /* Nobody cleared yet, and not in preview. Says what is true rather than
     pretending the page is coming soon. */
  if (!shown.length) {
    host.innerHTML =
      '<div class="grad-empty">' +
        '<div>' +
          '<strong>No trainer profiles are published yet</strong>' +
          '<p>Our skills courses are delivered by specialists from Centenary’s network. ' +
            'Naming somebody here means putting their name and professional history on a ' +
            'public page, which we do not do before they have agreed to it.</p>' +
        '</div>' +
        '<a href="contact" class="btn btn-primary">Ask about a course</a>' +
      '</div>';
    return;
  }

  var html = '';

  if (REVIEW) {
    /* The static noindex lives in trainers.html — but this file is on the sync
       manifest and that file is not, so a sync would carry uncleared names to
       three more public sites and leave the protection behind. Put it back from
       here if it is missing. A tag added by script is weaker than one in the
       source, which is why trainers.html still has its own; this is the
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
        '<p>This is how the page will look. Neither specialist has been asked whether ' +
          'their name and professional history may appear on four public academy ' +
          'websites, and neither engagement is signed — so nothing here is approved for ' +
          'publication, and the page is hidden from search engines until it is.</p>' +
        '<p>What is still needed: a yes from each of them, the engagement confirmed, ' +
          'Fiston’s surname and profile, and a photograph each if they want one.</p>' +
      '</div>';
  }

  host.innerHTML = html + '<div class="trainergrid">' + shown.map(card).join('') + '</div>';
})();
