/* ---- Skills Development scorecard -----------------------------------------
   The B-BBEE Skills Development element as it came back on the verification
   report, plus — for each line — what it actually measures and which part of
   the academy feeds it.

   READ THIS BEFORE EDITING.

   1. THE NUMBERS ARE THE VERIFICATION REPORT'S, NOT OURS. points, target,
      actual and score below are transcribed from the report and nothing else.
      The academy does not calculate B-BBEE and must never look as though it
      does. If a figure here disagrees with the report, the report is right.

   2. WHAT IS DERIVED, AND WHY IT IS SAFE. Two things on the page are computed
      rather than transcribed: the total (the twelve scores added up) and the
      shortfall per line (points minus score). Both were checked against the
      report — the scores sum to 24.63 exactly, and every line follows
      score = min(actual / target, 1) x points to the cent. That is arithmetic
      on the report's own figures, not a second opinion about the score, and
      the page says so. Do not extend it into predicting a score or a level.

   3. WHERE THE FIGURES COME FROM. Final BEE Verification Report for SPS RSA
      (Pty) Ltd, certificate SPSGEN069, issued 03 August 2026 — form COR 40
      Rev 2. The report itself lives in private/, which .htaccess 404s and the
      deploy mirror excludes: it carries the company registration number, VAT
      number, physical address and the ownership percentages, none of which
      appear on this page or belong on a public site. Do not copy it back into
      the web root.

   4. THE OTHER FOUR ELEMENTS ARE CONTEXT, NOT DETAIL. `OVERALL` below is the
      report's scorecard overview — five element totals, the status level, and
      nothing underneath them. Skills Development is the element this academy
      is inside, so it is the only one broken down by indicator. Resist the urge
      to render the other four; the academy has no business explaining a
      procurement scorecard.

   5. THE MATRIX CATEGORY OF A COURSE IS NOT OURS TO ASSIGN. Which of
      Categories A-G a given course falls into is the B-BBEE practitioner's
      call, and it changes what the course is worth. `feeds` below says what
      kind of activity counts, never "this course is a Category C".

   6. THE CERTIFICATE EXPIRES 02 AUGUST 2027. Everything on this page is one
      measurement period, already closed (01 Mar 2025 - 28 Feb 2026). When the
      next report lands, this file is what gets replaced — not patched.

   See §1 of the README: there is a standing instruction against a B-BBEE
   *sales* angle. This page is reporting, for SPS, about SPS — what the academy
   contributed to a scorecard SPS already has. It is not a reason to enrol. */
(function () {
  /* The report's own scorecard overview, section 2 and 3. Totals only. */
  var OVERALL = {
    entity: 'SPS RSA (Pty) Ltd',
    code: 'Amended Construction Sector Codes of Good Practice',
    period: '01 Mar 2025 – 28 Feb 2026',
    verified: '07 July 2026',
    issued: '03 August 2026',
    expires: '02 August 2027',
    certificate: 'SPSGEN069',
    level: 'Level 2',
    recognition: '125%',
    total: 95.03,
    elements: [
      { name: 'Ownership', score: 22.00 },
      { name: 'Management Control', score: 14.20 },
      { name: 'Skills Development', score: 24.63, here: true },
      { name: 'Enterprise & Supplier Development', score: 28.21 },
      { name: 'Socio-Economic Development', score: 6.00 }
    ]
  };

  var SCORECARD = {
    element: 'Skills Development',
    groups: [
      {
        sub: 'Skills Development Expenditure',
        rows: [
          {
            indicator: 'Skills Development Expenditure on Learning Programmes specified in the Learning Programme Matrix for black people as a percentage of Leviable Amount',
            points: 4, target: '3.00%', actual: '2.97%', score: 3.96,
            targetN: 3.00, actualN: 2.97,
            plain: 'What SPS spent on training black employees, measured against the Leviable Amount — the payroll figure the skills levy is charged on. It carries more weight than any other line on the element.',
            feeds: 'Every fully funded course a black employee takes is spend on this line: the accredited qualification, the AI short courses, the artisan programmes, all of it. This is the one indicator where simply enrolling more people moves the number.',
            links: [{ t: 'The whole catalogue', h: 'courses' }]
          },
          {
            indicator: 'The Proportion of Skills Development Expenditure on Black people by the Measured Entity using the Adjusted Recognition for Gender expended on African People',
            points: 2, target: '41.90%', actual: '75.33%', score: 2.00,
            targetN: 41.90, actualN: 75.33,
            plain: 'Of the money spent on black employees, how much went to African employees. “Adjusted Recognition for Gender” is the weighting that stops the spend counting fully if it all goes one way on gender.',
            feeds: 'Driven by who is enrolled rather than by which courses exist. Already at full marks, with the actual at almost twice the target.',
            links: []
          },
          {
            indicator: 'The Proportion of Skills Development Expenditure on Black people by the Measured Entity using the Adjusted Recognition for Gender expended on Black Management (Executive, Senior & Middle Management)',
            points: 2, target: '15.00%', actual: '27.64%', score: 2.00,
            targetN: 15.00, actualN: 27.64,
            plain: 'How much of the spend reached black employees in executive, senior and middle management.',
            feeds: 'The management-level courses in the Business School are what puts spend on this line — leadership, strategy and the AI courses written for people running teams rather than doing the task.',
            links: [{ t: 'AI for Leaders & Managers', h: 'course?c=ai-leaders' },
                    { t: 'AI Strategy for Business Leaders', h: 'courses#business' },
                    { t: 'Business School', h: 'courses#business' }]
          },
          {
            indicator: 'The Proportion of Skills Development Expenditure on Black people by the Measured Entity using the Adjusted Recognition for Gender expended on Black Management (Junior Management)',
            points: 1, target: '10.00%', actual: '61.79%', score: 1.00,
            targetN: 10.00, actualN: 61.79,
            plain: 'The same measure again, one layer down: spend reaching black employees in junior management.',
            feeds: 'Comfortably at full marks — the actual is six times the target, which says the academy is already reaching supervisors and team leads.',
            links: [{ t: 'Business School', h: 'courses#business' }]
          },
          {
            indicator: 'The Proportion of Skills Development Expenditure on Black people by the Measured Entity using the Adjusted Recognition for Gender expended on Bursaries or Scholarships for Black People',
            points: 2, target: '15.00%', actual: '23.00%', score: 2.00,
            targetN: 15.00, actualN: 23.00,
            plain: 'Spend that took the form of a bursary or a scholarship rather than a course booking.',
            feeds: 'Not something the academy books — bursaries are arranged by HR. Listed here because it is part of the same 11-point spend sub-element, and it is already at full marks.',
            links: []
          }
        ]
      },
      {
        sub: 'Learnerships, Apprenticeships, Internships and Professional Registrations',
        rows: [
          {
            indicator: 'Number of black people participating in Category A, B, C or D learning programmes as per the Learning Programme Matrix, as a percentage of the total number of employees',
            points: 3, target: '2.50%', actual: '72.73%', score: 3.00,
            targetN: 2.50, actualN: 72.73,
            plain: 'Headcount rather than money: how many black employees are on a structured learning programme — Categories A to D of the Learning Programme Matrix, which is where bursaries, learnerships, apprenticeships and occupationally-directed qualifications sit — as a share of everyone employed.',
            feeds: 'The structured, registered programmes are what count here: the accredited Occupational Certificate: Project Manager, and the artisan and trade pathways. Which Matrix category any given course falls into is confirmed by the B-BBEE practitioner, not by the academy.',
            links: [{ t: 'Occupational Certificate: Project Manager', h: 'course?c=project-management' },
                    { t: 'Technical & Artisan Programmes', h: 'programmes' }]
          },
          {
            indicator: 'Number of Black Employees registered as candidates with industry professional registration bodies as a % of the total number of such registered Employees',
            points: 3, target: '60.00%', actual: '33.33%', score: 1.67,
            targetN: 60.00, actualN: 33.33,
            plain: 'Of the employees registered as candidates with an industry professional registration body, what share are black. Candidacy is the step before full professional registration.',
            feeds: 'This is the weakest line on the element and the one the academy is best placed to move. The accredited qualification is the recognised route toward professional standing; the bonus line further down shows that the employees who do reach full registration are already 100% black, so the shortfall is in the pipeline into candidacy rather than at the end of it.',
            links: [{ t: 'Occupational Certificate: Project Manager', h: 'course?c=project-management' },
                    { t: 'Already have the Google certificate?', h: 'pm-pathway' }]
          },
          {
            indicator: 'Number of Black People with Disabilities on Category A, B, C or D programmes as per the Learning Programme Matrix, as a percentage of black office based learners on those learning programmes',
            points: 1, target: '5.00%', actual: '13.33%', score: 1.00,
            targetN: 5.00, actualN: 13.33,
            plain: 'Of the black office-based learners on those structured programmes, how many are people with disabilities.',
            feeds: 'A matter of who is enrolled on the structured programmes. At full marks, with the actual at more than twice the target.',
            links: []
          }
        ]
      },
      {
        sub: 'Mentorship',
        rows: [
          {
            indicator: 'Implementation of an approved and verified Mentorship Program',
            points: 3, target: 'Yes', actual: 'Yes', score: 3.00,
            targetN: null, actualN: null,
            plain: 'A yes-or-no line. Either there is an approved and verified mentorship programme, or there is not. Three points, all or nothing.',
            feeds: 'The academy’s mentorship element, and the trainers behind it. Full marks — but it has to be re-approved and re-verified each measurement period to stay that way, so this is three points that are kept rather than won.',
            links: [{ t: 'Meet the trainers', h: 'trainers' }]
          }
        ]
      },
      {
        sub: 'Bonus Points',
        bonus: true,
        rows: [
          {
            indicator: 'Number of black people absorbed by the Measured Entity at the end of a Category A, B, C or D learning programme',
            points: 1, target: '100.00%', actual: '100.00%', score: 1.00,
            targetN: 100, actualN: 100,
            plain: 'Of the people who finished a structured learning programme, how many SPS kept on afterwards.',
            feeds: 'Everyone who finished was absorbed. It is a bonus point that depends on what happens after the programme, not during it.',
            links: [{ t: 'Where our graduates are', h: 'graduates' }]
          },
          {
            indicator: 'The number of black employees that completed a Mentorship Programme during the last 3 years that were promoted during the Measurement Period expressed as a percentage of all such employees during those 3 years',
            points: 2, target: '15.00%', actual: '100.00%', score: 2.00,
            targetN: 15.00, actualN: 100.00,
            plain: 'Of the black employees who finished a mentorship programme in the last three years, how many were promoted during this measurement period.',
            feeds: 'Against a 15% target, every one of them was promoted. This is the clearest evidence on the whole scorecard that the mentorship element does something after the certificate.',
            links: [{ t: 'Meet the trainers', h: 'trainers' }]
          },
          {
            indicator: 'Number of Black Employees who registered as professionals with industry professional bodies as a % of all Employees who registered as such in the Measurement Period',
            points: 2, target: '60.00%', actual: '100.00%', score: 2.00,
            targetN: 60.00, actualN: 100.00,
            plain: 'Of everyone who registered as a full professional with an industry body this period, what share were black employees.',
            feeds: 'Full marks — and worth reading directly against the candidate-registration line above, which scored a third of its points. Everyone who got to the end got there; the shortage is further back, in who is entering candidacy.',
            links: []
          }
        ]
      }
    ]
  };

  var host = document.getElementById('scorecard');
  if (!host) return;

  /* ---- derived, and only from the report's own figures (see note 2) ---- */
  var rows = [], core = 0, bonus = 0, scored = 0, coreScore = 0, bonusScore = 0;
  SCORECARD.groups.forEach(function (g) {
    g.rows.forEach(function (r) {
      r.sub = g.sub; r.isBonus = !!g.bonus;
      r.lost = Math.round((r.points - r.score) * 100) / 100;
      rows.push(r);
      scored += r.score;
      if (g.bonus) { bonus += r.points; bonusScore += r.score; }
      else { core += r.points; coreScore += r.score; }
    });
  });
  scored = Math.round(scored * 100) / 100;
  var available = core + bonus;
  var lost = Math.round((available - scored) * 100) / 100;
  var short = rows.filter(function (r) { return r.lost > 0; })
                  .sort(function (a, b) { return b.lost - a.lost; });

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function pts(n) { return n.toFixed(2); }

  /* ---- where this element sits in the whole scorecard ----
     Totals only, straight off the report's overview. The point of the band is
     that Skills Development is not a rounding error on the certificate: it is
     the second-largest of the five element scores. */
  var max = Math.max.apply(null, OVERALL.elements.map(function (e) { return e.score; }));
  var rank = OVERALL.elements.slice().sort(function (a, b) { return b.score - a.score; })
                    .findIndex(function (e) { return e.here; }) + 1;
  var ord = ['', 'largest', 'second-largest', 'third-largest', 'fourth-largest', 'smallest'][rank];
  var here = OVERALL.elements.filter(function (e) { return e.here; })[0];

  /* The five element scores as printed add to 95.04; the certificate states
     95.03. The report rounds each element to two decimals and totals the
     unrounded figures, so the cent is lost on the way. Anybody who adds the
     column up will find this, so the page says it first — and says which
     number governs. Do not "fix" it by adjusting an element score. */
  var elemSum = Math.round(OVERALL.elements.reduce(function (n, e) { return n + e.score; }, 0) * 100) / 100;
  var sumNote = Math.abs(elemSum - OVERALL.total) < 0.005 ? '' :
    ' <span class="sc-rounding">The five scores as printed add to ' + elemSum.toFixed(2) +
    ' rather than ' + OVERALL.total.toFixed(2) + '; the report rounds each element to two decimals ' +
    'and totals the unrounded figures. The certificate&rsquo;s ' + OVERALL.total.toFixed(2) +
    ' is the one that counts.</span>';

  var overall = '<div class="sc-overall">' +
    '<div class="sc-oh">' +
      '<div><span class="eyebrow">The certificate</span>' +
      '<h3>' + esc(OVERALL.level) + ', at ' + OVERALL.total.toFixed(2) + ' points</h3>' +
      '<p>' + esc(OVERALL.entity) + ' &middot; ' + esc(OVERALL.code) + ' &middot; measured ' +
      esc(OVERALL.period) + '. Certificate ' + esc(OVERALL.certificate) +
      ', issued ' + esc(OVERALL.issued) + ', expires ' + esc(OVERALL.expires) +
      ' &middot; B-BBEE recognition ' + esc(OVERALL.recognition) + '.</p></div>' +
    '</div>' +
    '<ul class="sc-elems">' + OVERALL.elements.map(function (e) {
      return '<li class="sc-elem' + (e.here ? ' is-here' : '') + '">' +
        '<span class="sc-elem-n">' + esc(e.name) + (e.here ? ' <em>you are here</em>' : '') + '</span>' +
        '<span class="sc-elem-bar"><i style="width:' + (e.score / max * 100).toFixed(1) + '%"></i></span>' +
        '<span class="sc-elem-s">' + e.score.toFixed(2) + '</span></li>';
    }).join('') + '</ul>' +
    '<p class="sc-overall-foot">Skills Development is the <strong>' + ord + '</strong> of the five ' +
    'element scores on this certificate &mdash; ' +
    '<strong>' + Math.round(here.score / OVERALL.total * 100) + '%</strong> of the ' +
    'total. It is also the only one of the five the academy sits inside, which is why it is the ' +
    'only one broken down below.' + sumNote + '</p></div>';

  /* ---- the headline band ---- */
  var head = '<div class="sc-top">' +
    '<div class="sc-big"><span class="sc-num">' + pts(scored) + '</span>' +
    '<span class="sc-of">of ' + available + ' points available</span>' +
    '<span class="sc-sub">' + core + ' on the element itself, ' + bonus + ' in bonus points. ' +
    'SPS scored ' + pts(coreScore) + ' and ' + pts(bonusScore) + '.</span></div>' +
    '<div class="sc-gap"><strong>' + pts(lost) + ' points missing, across ' + short.length + ' lines</strong>' +
    '<p>Everything else on the element is at full marks. Of what is missing, ' +
    '<strong>' + Math.round(short[0].lost / lost * 100) + '%</strong> sits on a single indicator:</p>' +
    '<ul>' + short.map(function (r) {
      return '<li><span class="sc-gap-n">&minus;' + pts(r.lost) + '</span> ' +
             esc(r.indicator.length > 96 ? r.indicator.slice(0, 96) + '…' : r.indicator) +
             ' <em>(' + r.actual + ' against a ' + r.target + ' target)</em></li>';
    }).join('') + '</ul></div></div>';

  /* ---- the table, one <details> per indicator so the report reads as the
     report and the explanation is a click away rather than in the way ---- */
  var body = SCORECARD.groups.map(function (g) {
    return '<div class="sc-group">' +
      '<h3 class="sc-sub-h">' + esc(g.sub) + (g.bonus ? ' <span class="sc-tag">bonus</span>' : '') + '</h3>' +
      g.rows.map(function (r) {
        var pct = r.targetN ? Math.min(r.actualN / r.targetN, 1) * 100 : (r.score ? 100 : 0);
        var full = r.lost <= 0;
        return '<details class="sc-row' + (full ? ' is-full' : ' is-short') + '">' +
          '<summary>' +
            '<span class="sc-ind">' + esc(r.indicator) + '</span>' +
            '<span class="sc-figs">' +
              '<span class="sc-f"><b>' + r.target + '</b><i>Target</i></span>' +
              '<span class="sc-f"><b>' + r.actual + '</b><i>Actual</i></span>' +
              '<span class="sc-f sc-f-score"><b>' + pts(r.score) + '</b><i>of ' + r.points + '</i></span>' +
            '</span>' +
            '<span class="sc-bar" aria-hidden="true"><i style="width:' + pct.toFixed(1) + '%"></i></span>' +
          '</summary>' +
          '<div class="sc-body">' +
            '<p class="sc-plain"><strong>What it measures.</strong> ' + esc(r.plain) + '</p>' +
            '<p class="sc-feeds"><strong>What feeds it.</strong> ' + esc(r.feeds) + '</p>' +
            (r.links.length ? '<p class="sc-links">' + r.links.map(function (l) {
              return '<a href="' + l.h + '">' + esc(l.t) + '</a>';
            }).join('') + '</p>' : '') +
          '</div>' +
        '</details>';
      }).join('') + '</div>';
  }).join('');

  host.innerHTML = overall + head + '<div class="sc-table">' + body + '</div>' +
    '<p class="sc-foot">Scores add to <strong>' + pts(scored) + '</strong>. Every line follows ' +
    '<code>score = min(actual &divide; target, 1) &times; weighting points</code>, which is why an ' +
    'indicator far past its target still stops at its weighting and cannot subsidise a weak one.</p>';

  /* One open at a time. Twelve indicators all expanded is the wall of text
     this page exists to replace. */
  var all = [].slice.call(host.querySelectorAll('details.sc-row'));
  all.forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (!d.open) return;
      all.forEach(function (o) { if (o !== d) o.open = false; });
    });
  });
})();
