/* Study-only nav for a signed-in learner, on the public pages (SPS, 10 Nov 2026).
 *
 * Sibusiso: "once logged in as students you only see studying related tabs" —
 * clicking Home or Courses showed the public menu with "Sign in" in it, which
 * reads as having been signed out. The PHP learner pages already print the
 * study nav (lib/chrome.php, behind 'self_signup'); this does the same on the
 * static pages a learner still opens — a module, a course page, the planner —
 * and on the shared PHP pages that use the public nav (contact, privacy).
 *
 * Only for role 'learner'. Staff keep the public nav, because they do need it.
 * Signed out, or the probe fails: nothing happens and the page is as it was.
 *
 * Keep the links in step with chrome_nav('learner') in lib/chrome.php.
 */
(function () {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  /* The sidebar on the static pages (19 Nov 2026, Sibusiso: "the menu always
     on the left, like the new UI"). The same markup chrome_shell_frame() in
     lib/chrome.php prints on the PHP pages — keep the two in step. The public
     top nav is swapped out once the account probe says this is a learner. */
  var ICON = {
    home:  '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    skills:'<path d="M12 3l3 6H9zM12 9l4 7H8zM12 16v5M8 21h8"/>',
    files: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>',
    tutor: '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5M12 16.5h.01"/>',
    me:    '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    bell:  '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
    menu:  '<path d="M3 6h18M3 12h18M3 18h18"/>'
  };
  function svg(p) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
           'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
  }

  function shell(s, page) {
    var nav = document.getElementById('nav');
    if (!nav || document.querySelector('body > .app-side')) return;
    var logo = nav.querySelector('.brand img');
    var src = logo ? logo.getAttribute('src') : '', alt = logo ? logo.getAttribute('alt') : '';
    /* The study pages a learner reaches from Skills light up Skills. */
    var here = { dashboard: 'home', module: 'skills', course: 'skills', 'pm-schedule': 'skills',
                 'pm-pathway': 'skills', skills: 'skills', files: 'files', tutor: 'tutor', me: 'me' }[page] || '';
    var items = [['dashboard', 'home', 'Home'], ['skills', 'skills', 'Skills'], ['files', 'files', 'My files']];
    if (s.tutor) items.push(['tutor', 'tutor', 'Tutor']);
    items.push(['me', 'me', 'Account']);

    var link = document.createElement('link');
    link.rel = 'stylesheet';
    var me = document.querySelector('script[src*="learner-nav.js"]');
    var v = me ? (me.getAttribute('src').split('?')[1] || '') : '';
    link.href = 'app.css' + (v ? '?' + v : '');
    document.head.appendChild(link);

    var side = document.createElement('aside');
    side.className = 'app-side';
    side.id = 'navLinks';
    side.innerHTML =
      '<a href="dashboard" class="app-logo"><img src="' + esc(src) + '" alt="' + esc(alt) + '"></a>' +
      '<div class="app-menu" role="navigation" aria-label="Your learning">' +
      items.map(function (i) {
        return '<a href="' + i[0] + '"' + (here === i[1] ? ' class="on" aria-current="page"' : '') + '>' +
               svg(ICON[i[1]]) + '<span>' + esc(i[2]) + '</span></a>';
      }).join('') + '</div>' +
      '<div class="app-side-foot"><a href="contact">Contact the academy</a>' +
      '<form method="POST" action="logout"><input type="hidden" name="_token" value="' + esc(s.token || '') + '">' +
      '<button type="submit" class="linkish">Sign out</button></form></div>' +
      /* profile.js adds a "Sign in" link to #navLinks unless one is already there. */
      '<span class="nav-signin" hidden></span>';

    var top = document.createElement('header');
    top.className = 'app-top';
    top.innerHTML =
      '<button class="app-burger" type="button" aria-label="Menu" aria-expanded="false">' + svg(ICON.menu) + '</button>' +
      '<a href="dashboard" class="app-top-logo"><img src="' + esc(src) + '" alt=""></a>' +
      '<span class="app-top-gap"></span>' +
      '<a href="notices" class="app-bell" title="Notice board">' + svg(ICON.bell) + '</a>' +
      '<a href="me" class="app-av" title="' + esc(s.name || '') + '">' + esc(s.initials || '') + '</a>';

    nav.replaceWith(top);
    document.body.insertBefore(side, document.body.firstChild);
    document.body.classList.add('has-app-side');

    /* Our own phone toggle: site.js bound its handler to the old nav's button. */
    var burger = top.querySelector('.app-burger');
    burger.addEventListener('click', function () {
      var open = side.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function paint(s) {
    if (!s || s.role !== 'learner') return;
    var links = document.getElementById('navLinks');
    if (!links) return;

    var page = location.pathname.replace(/\/+$/, '').split('/').pop().replace(/\.(php|html)$/, '');
    if (s.shell) { shell(s, page); }
    /* Where the site has the app frame (s.shell, from brand 'app_shell', 16 Nov
       2026) the same four sections as its sidebar — chrome_app_shell(). */
    var items = s.shell ? [
      ['dashboard', 'Home', 'dashboard'],
      ['skills',    'Skills', 'skills'],
      ['files',     'My files', 'files'],
      ['me',        'Account', 'me'],
      ['contact',   'Contact', 'contact']
    ] : [
      ['my#account', 'My account', ''],
      ['my',         'My learning', 'my'],
      ['files',      'My files', 'files'],
      ['logbook',    'My logbook', 'logbook'],
      ['submit',     'Submit work', 'submit'],
      ['notices',    'Notice board', 'notices'],
      ['contact',    'Contact', 'contact']
    ];
    /* The study tutor (s.tutor, brand 'tutor', 16 Nov 2026): a menu item, and on
       a module page its "Ask your tutor" panel (tutor.js, same version as this). */
    if (s.tutor) {
      items.splice(s.shell ? 3 : 2, 0, ['tutor', 'Tutor', 'tutor']);
      if (page === 'module' && !window.SPSTutor) {
        var me = document.querySelector('script[src*="learner-nav.js"]');
        var v = me ? (me.getAttribute('src').split('?')[1] || '') : '';
        var t = document.createElement('script');
        t.src = 'tutor.js' + (v ? '?' + v : '');
        t.onload = function () { if (window.SPSTutor) window.SPSTutor.floating(s); };
        document.body.appendChild(t);
      }
    }
    links.innerHTML = items.map(function (i) {
      return '<a href="' + i[0] + '"' + (i[2] && i[2] === page ? ' class="active"' : '') + '>' + esc(i[1]) + '</a>';
    }).join('') +
      '<form method="POST" action="logout" style="display:inline">' +
      '<input type="hidden" name="_token" value="' + esc(s.token || '') + '">' +
      '<button type="submit" class="linkish">Sign out</button></form>';

    var logo = document.querySelector('#nav .brand');
    if (logo) logo.setAttribute('href', s.shell ? 'dashboard' : 'my');
  }

  if (window.SPSAccount && window.SPSAccount.ready) {
    window.SPSAccount.ready(paint);
  } else {
    fetch('account.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (j) { paint(j && j.in ? j : null); })
      .catch(function () {});
  }
})();
