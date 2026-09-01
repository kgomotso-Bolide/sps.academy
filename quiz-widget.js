/* The "Check yourself" card on the module page.
 *
 * Same shape as materials.js and the same reason for it: this asks
 * quiz.php?course=… for what THIS learner is allowed to see, and does
 * nothing when the answer is anonymous, not enrolled, or simply "no quiz for
 * this module yet" — module.html's #m-quizsec starts hidden precisely so
 * that "nothing to show" reads as no section at all, not an empty one.
 *
 * Kept as its own file rather than folded into materials.js, which is
 * deliberately scoped to course MATERIAL per its own header comment — a
 * quiz is a different kind of thing with a different owner (lib/quiz.php),
 * and growing materials.js into a second responsibility is how a "shared"
 * script stops being safe to change for either reason.
 */
(function () {
  'use strict';

  var section = document.getElementById('m-quizsec');
  var host    = document.getElementById('m-quiz');
  if (!section || !host) return;                      // not a module page

  var params = new URLSearchParams(location.search);
  var code   = (params.get('m') || '').toUpperCase();
  if (!code) return;

  /* Same constant as materials.js, for the same reason — one course carries
     a tracked curriculum today, and adding a second is a visible change here
     and in learner_catalogue(), not an inference that quietly breaks. */
  var COURSE = 'project-management';

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  fetch('quiz.php?course=' + encodeURIComponent(COURSE), {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' }
  })
    .then(function (r) {
      if (!r.ok && r.status !== 403) throw new Error('http ' + r.status);
      return r.json();
    })
    .then(function (data) {
      if (!data || !data.in || !data.enrolled || !data.quizzes) return;

      var mine = data.quizzes[code];
      if (!mine || !mine.available) return;

      var href = 'quiz?course=' + encodeURIComponent(COURSE) + '&module=' + encodeURIComponent(code);
      var scoreLine = mine.attempts > 0
        ? '<strong>' + esc(mine.best_pct) + '%</strong> best of ' + esc(mine.attempts) +
          ' attempt' + (mine.attempts === 1 ? '' : 's')
        : '<strong>' + esc(mine.questions) + '</strong> question' + (mine.questions === 1 ? '' : 's') + ', not attempted yet';

      host.innerHTML =
        '<div class="qz-card">' +
          '<div class="qz-card-score">' + scoreLine + '</div>' +
          '<a class="btn btn-primary" href="' + esc(href) + '">' +
            (mine.attempts > 0 ? 'Try again' : 'Take the self-check') + '</a>' +
        '</div>';
      section.style.display = '';
    })
    .catch(function () {
      /* Silent on purpose, same reasoning as materials.js: every failure
         here means "this visitor gets no self-check card", and the section
         is already hidden for exactly that case. */
    });
})();
