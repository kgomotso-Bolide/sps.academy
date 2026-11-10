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

  function paint(s) {
    if (!s || s.role !== 'learner') return;
    var links = document.getElementById('navLinks');
    if (!links) return;

    var page = location.pathname.replace(/\/+$/, '').split('/').pop().replace(/\.(php|html)$/, '');
    var items = [
      ['my#account', 'My account', ''],
      ['my',         'My learning', 'my'],
      ['files',      'My files', 'files'],
      ['logbook',    'My logbook', 'logbook'],
      ['submit',     'Submit work', 'submit'],
      ['notices',    'Notice board', 'notices'],
      ['contact',    'Contact', 'contact']
    ];
    links.innerHTML = items.map(function (i) {
      return '<a href="' + i[0] + '"' + (i[2] && i[2] === page ? ' class="active"' : '') + '>' + esc(i[1]) + '</a>';
    }).join('') +
      '<form method="POST" action="logout" style="display:inline">' +
      '<input type="hidden" name="_token" value="' + esc(s.token || '') + '">' +
      '<button type="submit" class="linkish">Sign out</button></form>';

    var logo = document.querySelector('#nav .brand');
    if (logo) logo.setAttribute('href', 'my');
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
