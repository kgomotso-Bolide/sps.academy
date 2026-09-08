/* The self-check questions on the module page, one set per topic.
 *
 * Same shape as materials.js and the same reason for it: this asks
 * quiz.php?course=… for what THIS learner is allowed to see, and does nothing
 * when the answer is anonymous, not enrolled, or simply "no questions written
 * for this yet".
 *
 * PER TOPIC, NOT PER MODULE (changed 8 Sep 2026)
 * ---------------------------------------------
 * A learner reads one topic and then answers questions on it, so that is where
 * the questions belong — beside the reading, on the same card, rather than in
 * one pile at the foot of the module. Centenary's own question bank is written
 * the same way: ten questions per topic.
 *
 * The lookup key is therefore a topic code (KM-01-KT01) rather than a module
 * code. quiz.php returns whatever codes have questions, so a quiz saved against
 * a bare module code before today still comes back — it is rendered in the
 * module-level card below, which is why that path is kept rather than deleted.
 *
 * Kept as its own file rather than folded into materials.js, which is
 * deliberately scoped to course MATERIAL per its own header comment — a quiz is
 * a different kind of thing with a different owner (lib/quiz.php), and growing
 * materials.js into a second responsibility is how a "shared" script stops
 * being safe to change for either reason.
 */
(function () {
  'use strict';

  var params = new URLSearchParams(location.search);
  var code   = (params.get('m') || '').toUpperCase();
  if (!code) return;

  /* Same constant as materials.js, for the same reason — one course carries
     a tracked curriculum today, and adding a second is a visible change here
     and in learner_catalogue(), not an inference that quietly breaks. */
  var COURSE = 'project-management';

  var section = document.getElementById('m-quizsec');
  var host    = document.getElementById('m-quiz');

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function link(unit) {
    return 'quiz?course=' + encodeURIComponent(COURSE) + '&module=' + encodeURIComponent(unit);
  }

  function scoreLine(q) {
    return q.attempts > 0
      ? '<strong>' + esc(q.best_pct) + '%</strong> best of ' + esc(q.attempts) +
        ' attempt' + (q.attempts === 1 ? '' : 's')
      : '<strong>' + esc(q.questions) + '</strong> question' +
        (q.questions === 1 ? '' : 's') + ', not attempted yet';
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

      /* Per topic, onto each topic card. */
      document.querySelectorAll('#m-topics .module').forEach(function (card) {
        var meta = card.querySelector('.meta');
        if (!meta) return;
        var topic = (meta.textContent || '').split('·')[0].trim();
        var mine  = data.quizzes[topic];
        if (!mine || !mine.available) return;

        var wrap = document.createElement('div');
        wrap.className = 'qz-topic';
        wrap.innerHTML =
          '<span class="qz-topic-score">' + scoreLine(mine) + '</span>' +
          '<a class="btn btn-ghost" href="' + esc(link(topic)) + '">' +
            (mine.attempts > 0 ? 'Try these questions again' : 'Answer the questions') + '</a>';

        var tick = card.querySelector('.topic-tick');
        if (tick) card.insertBefore(wrap, tick); else card.appendChild(wrap);
      });

      /* A module-level set, if one was ever saved. Nothing writes these any
         more, but an existing one stays reachable rather than disappearing
         with its learners' attempts still attached to it. */
      if (!section || !host) return;
      var whole = data.quizzes[code];
      if (!whole || !whole.available) return;

      host.innerHTML =
        '<div class="qz-card">' +
          '<div class="qz-card-score">' + scoreLine(whole) + '</div>' +
          '<a class="btn btn-primary" href="' + esc(link(code)) + '">' +
            (whole.attempts > 0 ? 'Try it again' : 'Start the self-check') + '</a>' +
        '</div>';
      section.hidden = false;
    })
    .catch(function () { /* silent, on purpose — see the note above */ });
})();
