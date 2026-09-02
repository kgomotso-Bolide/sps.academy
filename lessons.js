/* Written content under each area of a topic, on the module page.
 *
 * Same manners as materials.js and quiz-widget.js: it asks the server, and if
 * the answer is "not signed in", "not enrolled", or "nothing written yet" it
 * does nothing at all and leaves the page exactly as the curriculum rendered
 * it. A module with no written content must look deliberate, not broken —
 * most of them have none yet.
 *
 * The HTML comes from the server already escaped (see lib/sections.php on why
 * the stored form is plain text). This file does not build markup out of
 * anything a person typed; it only places what it was given.
 */
(function () {
  'use strict';

  var params = new URLSearchParams(location.search);
  var moduleCode = params.get('m');
  if (!moduleCode) return;

  var COURSE = 'project-management';

  fetch('lessons.php?course=' + encodeURIComponent(COURSE) +
        '&module=' + encodeURIComponent(moduleCode), { credentials: 'same-origin' })
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (data) {
      if (!data || !data.in || !data.enrolled || !data.sections) return;
      apply(data.sections);
    })
    .catch(function () { /* silent, on purpose — see the note above */ });

  function apply(sections) {
    /* The topic cards are rendered by module.html from pm-modules.js. Each
       topic card carries its code in .meta, and its areas are the .lesson
       rows inside it, in curriculum order — which is what area_index counts. */
    document.querySelectorAll('#m-topics .module').forEach(function (card) {
      var meta = card.querySelector('.meta');
      if (!meta) return;
      var code = (meta.textContent || '').split('·')[0].trim();
      var areas = sections[code];
      if (!areas) return;

      var rows = card.querySelectorAll('.lesson');
      rows.forEach(function (row, i) {
        var section = areas[String(i)];
        if (!section) return;
        makeExpandable(row, section, code, i);
      });

      var n = Object.keys(areas).length;
      var badge = document.createElement('span');
      badge.className = 'sec-badge';
      badge.textContent = n + (n === 1 ? ' section to read' : ' sections to read');
      meta.appendChild(document.createTextNode(' · '));
      meta.appendChild(badge);
    });
  }

  function makeExpandable(row, section, code, i) {
    var id = 'sec-' + code + '-' + i;

    row.style.cursor = 'pointer';
    row.classList.add('lesson-has-content');
    row.setAttribute('role', 'button');
    row.setAttribute('tabindex', '0');
    row.setAttribute('aria-expanded', 'false');
    row.setAttribute('aria-controls', id);

    var caret = document.createElement('span');
    caret.className = 'sec-caret';
    caret.setAttribute('aria-hidden', 'true');
    row.appendChild(caret);

    var body = document.createElement('div');
    body.className = 'sec-body';
    body.id = id;
    body.hidden = true;
    body.innerHTML = section.html;   // server-escaped; see the header note
    row.parentNode.insertBefore(body, row.nextSibling);

    function toggle() {
      var open = !body.hidden;
      body.hidden = open;
      row.setAttribute('aria-expanded', open ? 'false' : 'true');
      row.classList.toggle('open', !open);
    }

    row.addEventListener('click', toggle);
    row.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
  }
})();
