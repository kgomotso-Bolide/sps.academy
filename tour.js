/* The guided tour of the signed-in area (18 Nov 2026, Sibusiso).
 *
 * SHARED: lib/chrome.php's chrome_shell_frame() loads this where lib/brand.php
 * sets 'tour', with data-tour = learner | admin | trainer and data-uid.
 *
 * - Starts by itself once, the first time someone reaches their home page
 *   (dashboard for a learner, admin / trainer for staff). After Skip or Finish
 *   it does not come back on its own; "Take the tour" in the sidebar replays it.
 * - Skip tour, Back and Next on every step; Esc skips, arrow keys step.
 * - A step whose target is not on this page (no tutor here, no notice yet,
 *   a trainer's shorter menu) is left out, so one list serves every page.
 * - No movement: Kgomotso asked for no animation on the sites (3 Sep 2026).
 *   The highlight jumps; nothing slides or fades.
 * - "Seen" lives in localStorage, per site folder and per account. It is a
 *   convenience only: if storage is blocked the tour simply never auto-starts.
 */
(function () {
  'use strict';
  var me = document.querySelector('script[src*="tour.js"]');
  if (!me) return;
  var kind = me.getAttribute('data-tour') || 'learner';
  var uid  = me.getAttribute('data-uid') || '0';
  var site = (location.pathname.split('/')[1] || 'root');
  var KEY  = 'tour:' + site + ':' + kind + ':' + uid;
  var academy = me.getAttribute('data-academy') || 'the academy';

  var side = function (href) { return '#navLinks .app-menu a[href="' + href + '"]'; };

  var TOURS = {
    learner: [
      { t: 'Welcome to ' + academy, b: 'A quick look round your learning space. It takes under a minute, and you can skip it at any time.' },
      { el: '.dash-obj', t: 'Your next step', b: 'This always shows what to do next. Press Continue to pick up exactly where you left off.' },
      { el: side('dashboard'), t: 'Home', b: 'Your next step, your study streak and anything new from your facilitator.' },
      { el: side('skills'), t: 'Skills', b: 'Your whole course, module by module: the Reading, the topic quizzes, your logbook and the work you hand in.' },
      { el: side('files'), t: 'My files', b: 'Every guide and workbook for your course, ready to download.' },
      { el: side('tutor'), t: 'Your study tutor', b: 'Stuck on an idea? Ask the tutor. It explains from your own course material, summarises a module, or quizzes you.' },
      { el: side('me'), t: 'Account', b: 'Your details, your progress figures and your password.' },
      { el: '.app-bell', t: 'Notice board', b: 'Announcements from your facilitator: class dates, deadlines and changes.' },
      { el: '[data-tour-start]', t: 'That’s it', b: 'You can take this tour again any time from here. Good luck with your studies!' }
    ],
    admin: [
      { t: 'Welcome to the back office', b: 'A quick look round the administration side of ' + academy + '. Skip it at any time.' },
      { el: side('admin'), t: 'Registrations', b: 'New sign-ups and registrations arrive here.' },
      { el: side('admin-progress'), t: 'Progress', b: 'How far every learner has got, module by module, with reports to download.' },
      { el: side('admin-users'), t: 'Accounts', b: 'Set a password by hand, switch an account off, un-enrol a learner or delete an account.' },
      { el: side('admin-rpl'), t: 'RPL', b: 'Recognition of prior learning: review the evidence learners send in and recognise modules.' },
      { el: side('admin-materials'), t: 'Teaching', b: 'Upload material, the Reading and quizzes, run classes and registers, and check the files learners hand in.' },
      { el: side('admin-notices'), t: 'Notices', b: 'Post announcements to learners. They show on the learners’ Home and notice board.' },
      { el: side('admin-evidence'), t: 'B-BBEE', b: 'The training evidence for the scorecard, ready to export.' },
      { el: '#navLinks .app-side-foot a[href="./"]', t: 'View the site', b: 'Opens the public website, as visitors see it.' },
      { el: '[data-tour-start]', t: 'That’s it', b: 'You can take this tour again any time from here.' }
    ],
    trainer: [
      { t: 'Welcome, facilitator', b: 'A quick look round your side of ' + academy + '. Skip it at any time.' },
      { el: side('trainer'), t: 'My learners', b: 'The learners in your classes and how far each has got.' },
      { el: side('admin-classes'), t: 'Classes', b: 'Your in-person classes. Mark the attendance register here.' },
      { el: side('admin-materials'), t: 'Teaching', b: 'Course material, the Reading and quizzes, and the files learners hand in.' },
      { el: side('admin-notices'), t: 'Notices', b: 'Post announcements to your learners.' },
      { el: '[data-tour-start]', t: 'That’s it', b: 'You can take this tour again any time from here.' }
    ]
  };
  var HOME = { learner: /\/dashboard(\.php)?$/, admin: /\/admin(\.php)?$/, trainer: /\/trainer(\.php)?$/ };

  var seen = function () { try { return localStorage.getItem(KEY) === 'done'; } catch (e) { return true; } };
  var markSeen = function () { try { localStorage.setItem(KEY, 'done'); } catch (e) {} };

  var links = document.getElementById('navLinks');
  var narrow = function () { return window.matchMedia('(max-width: 900px)').matches; };
  var steps, i, ui, lastFocus;

  function present(s) {
    if (!s.el) return true;
    var n = document.querySelector(s.el);
    /* The phone sidebar is off screen but still counts: go() opens it. */
    return !!n && (n.getClientRects().length > 0 || !!(links && links.contains(n)));
  }

  function build() {
    ui = {};
    ui.block = el('div', 'tour-block');
    ui.spot  = el('div', 'tour-spot');
    ui.card  = el('div', 'tour-card');
    ui.card.setAttribute('role', 'dialog');
    ui.card.setAttribute('aria-labelledby', 'tourTitle');
    ui.card.setAttribute('aria-describedby', 'tourBody');
    ui.count = el('span', 'tour-count');
    ui.title = el('h2'); ui.title.id = 'tourTitle';
    ui.body  = el('p');  ui.body.id = 'tourBody';
    var row  = el('div', 'tour-row');
    ui.skip  = btn('Skip tour', 'tour-skip');
    ui.back  = btn('Back', 'tour-back');
    ui.next  = btn('Next', 'tour-next');
    row.appendChild(ui.skip); row.appendChild(el('span', 'tour-gap')); row.appendChild(ui.back); row.appendChild(ui.next);
    ui.card.appendChild(ui.count); ui.card.appendChild(ui.title); ui.card.appendChild(ui.body); ui.card.appendChild(row);
    ui.skip.addEventListener('click', function () { end(); });
    ui.back.addEventListener('click', function () { go(i - 1); });
    ui.next.addEventListener('click', function () { i >= steps.length - 1 ? end() : go(i + 1); });
    document.body.appendChild(ui.block); document.body.appendChild(ui.spot); document.body.appendChild(ui.card);
    document.addEventListener('keydown', onKey, true);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);
  }
  function el(tag, cls) { var n = document.createElement(tag); if (cls) n.className = cls; return n; }
  function btn(text, cls) { var b = el('button', cls); b.type = 'button'; b.textContent = text; return b; }

  function onKey(e) {
    if (!ui) return;
    if (e.key === 'Escape') { e.preventDefault(); end(); }
    else if (e.key === 'ArrowRight' && !/INPUT|TEXTAREA|SELECT/.test((e.target.tagName || ''))) { e.preventDefault(); ui.next.click(); }
    else if (e.key === 'ArrowLeft' && i > 0) { e.preventDefault(); go(i - 1); }
  }

  function target() { var s = steps[i]; return s && s.el ? document.querySelector(s.el) : null; }

  function go(n) {
    if (n < 0 || n >= steps.length) return;
    i = n;
    var s = steps[i], t = target();
    ui.count.textContent = (i + 1) + ' of ' + steps.length;
    ui.title.textContent = s.t;
    ui.body.textContent = s.b;
    ui.back.hidden = i === 0;
    ui.next.textContent = i === steps.length - 1 ? 'Finish' : 'Next';
    ui.skip.hidden = i === steps.length - 1;
    /* On a phone the sidebar is off screen; open it for the steps that live there. */
    var inSide = !!(t && links && links.contains(t));
    if (links && narrow()) links.classList.toggle('open', inSide);
    if (t && !inSide) t.scrollIntoView({ block: 'center', inline: 'nearest' });
    place();
    /* The phone sidebar slides in over .22s (app.css); measure again once it has. */
    if (inSide && narrow()) setTimeout(place, 260);
    ui.next.focus();
  }

  function place() {
    if (!ui) return;
    var t = target(), vw = window.innerWidth, vh = window.innerHeight, pad = 6, gap = 14;
    var c = ui.card, cw = Math.min(360, vw - 32);
    c.style.width = cw + 'px';
    var ch = c.offsetHeight;
    var r = t ? t.getBoundingClientRect() : null;
    if (!r || (r.width === 0 && r.height === 0)) {
      ui.spot.hidden = true;
      ui.block.classList.add('dim');
      c.style.left = Math.round((vw - cw) / 2) + 'px';
      c.style.top = Math.round(Math.max(16, (vh - ch) / 2)) + 'px';
      return;
    }
    ui.block.classList.remove('dim');
    ui.spot.hidden = false;
    ui.spot.style.left = (r.left - pad) + 'px';
    ui.spot.style.top = (r.top - pad) + 'px';
    ui.spot.style.width = (r.width + pad * 2) + 'px';
    ui.spot.style.height = (r.height + pad * 2) + 'px';
    var x, y;
    if (r.right + gap + cw <= vw - 16) { x = r.right + gap; y = r.top + r.height / 2 - ch / 2; }      // right
    else if (r.bottom + gap + ch <= vh - 16) { x = r.left + r.width / 2 - cw / 2; y = r.bottom + gap; } // below
    else if (r.top - gap - ch >= 16) { x = r.left + r.width / 2 - cw / 2; y = r.top - gap - ch; }     // above
    else { x = (vw - cw) / 2; y = vh - ch - 16; }                                                       // bottom of screen
    c.style.left = Math.round(Math.min(Math.max(16, x), vw - cw - 16)) + 'px';
    c.style.top = Math.round(Math.min(Math.max(16, y), vh - ch - 16)) + 'px';
  }

  function start() {
    if (ui) return;
    steps = (TOURS[kind] || TOURS.learner).filter(present);
    if (!steps.length) return;
    lastFocus = document.activeElement;
    build();
    go(0);
  }

  function end() {
    markSeen();
    if (!ui) return;
    document.removeEventListener('keydown', onKey, true);
    window.removeEventListener('resize', place);
    window.removeEventListener('scroll', place, true);
    [ui.block, ui.spot, ui.card].forEach(function (n) { n.remove(); });
    ui = null;
    if (links && narrow()) links.classList.remove('open');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-tour-start]');
    if (b) { e.preventDefault(); if (links) links.classList.remove('open'); start(); }
  });

  function auto() {
    if (seen() || !HOME[kind] || !HOME[kind].test(location.pathname)) return;
    start();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', auto); else auto();
})();
