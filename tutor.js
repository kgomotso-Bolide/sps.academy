/* The study tutor's conversation (16 Nov 2026). SHARED: synced to every academy.
 *
 * Two places, one renderer:
 *   - tutor.php: mounted inline in #tutor (data-course, data-module, data-token).
 *   - the module pages: learner-nav.js loads this file for a signed-in learner
 *     where the site has 'tutor', and window.SPSTutor.floating(session) adds an
 *     "Ask your tutor" button and panel that know which module is open.
 *
 * Answers come from tutor.php as typed blocks and are built here with
 * textContent only. Course text and anything a person typed are never parsed
 * as HTML.
 *
 * Colours come from the site's own variables. Text on the primary colour is
 * --tutor-on (white unless the site's styles.css says otherwise: Tracker's
 * yellow sets it dark).
 */
(function () {
  'use strict';
  if (window.SPSTutor) return;

  var CSS =
    '.tt{display:flex;flex-direction:column;min-height:0;background:#fff;border:1px solid var(--line,#ddd);border-radius:18px;overflow:hidden}' +
    '.tt-log{flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:12px;background:var(--bg-soft,#f7f6f4)}' +
    '.tt-m{max-width:88%;font-size:14.5px;line-height:1.55;padding:11px 14px;border-radius:14px;word-wrap:break-word}' +
    '.tt-m.me{align-self:flex-end;background:var(--orange);color:var(--tutor-on,#fff);border-bottom-right-radius:4px}' +
    '.tt-m.bot{align-self:flex-start;background:#fff;border:1px solid var(--line,#ddd);color:var(--ink,#222);border-bottom-left-radius:4px;display:flex;flex-direction:column;gap:9px}' +
    '.tt-m p{margin:0}.tt-m ul{margin:0;padding-left:20px}.tt-m li{margin:2px 0}' +
    '.tt-q{border-left:3px solid var(--orange);background:var(--orange-tint,#fdeee2);border-radius:0 10px 10px 0;padding:9px 12px;font-size:14px}' +
    '.tt-src{display:inline-block;margin-top:6px;font-size:12px;font-weight:700;color:var(--orange-deep);text-decoration:none}' +
    '.tt-src:hover{text-decoration:underline}' +
    '.tt-check{background:var(--green-tint,#eaf4e8);border-radius:10px;padding:9px 12px;font-size:13.5px;font-weight:600;color:var(--ink,#222)}' +
    '.tt-topic{border:1px solid var(--line,#ddd);border-radius:12px;padding:10px 12px}' +
    '.tt-topic b{display:block;margin-bottom:4px}.tt-topic .k{font-size:13px;color:var(--ink-soft,#555);margin-top:6px}' +
    '.tt-prac{border:1.5px dashed var(--orange);border-radius:12px;padding:12px}' +
    '.tt-prac strong{display:block;font-size:15px;margin-bottom:8px}' +
    '.tt-prac textarea{width:100%;min-height:64px;border:1px solid var(--line,#ddd);border-radius:8px;padding:8px;font:inherit;font-size:14px;resize:vertical;box-sizing:border-box}' +
    '.tt-prac .rv{margin-top:10px}.tt-prac .ans{margin-top:10px}' +
    '.tt-next{display:block;border:1px solid var(--line,#ddd);border-radius:12px;padding:10px 12px;text-decoration:none;color:var(--ink,#222)}' +
    '.tt-next:hover{border-color:var(--orange)}.tt-next b{display:block}.tt-next span{font-size:13px;color:var(--ink-soft,#555)}' +
    '.tt-chips{display:flex;flex-wrap:wrap;gap:6px;align-self:flex-start;max-width:96%}' +
    '.tt-chip,.tt-btn{background:#fff;border:1px solid var(--line,#ddd);border-radius:30px;padding:7px 12px;font:600 12.5px/1.2 inherit;font-family:inherit;color:var(--ink,#222);cursor:pointer}' +
    '.tt-chip:hover,.tt-btn:hover{border-color:var(--orange);color:var(--orange-deep)}' +
    '.tt-def{border-left:4px solid var(--green-deep,#2a7);background:var(--green-tint,#eaf4e8);border-radius:0 10px 10px 0;padding:10px 12px;font-size:14.5px;font-weight:600}' +
    '.tt-note{font-size:12.5px;color:var(--ink-soft,#666);font-style:italic}' +
    '.tt-cmp{display:grid;grid-template-columns:1fr 1fr;gap:8px}' +
    '.tt-cmp > div{border:1px solid var(--line,#ddd);border-radius:10px;padding:9px 11px;font-size:13.5px}' +
    '.tt-cmp b{display:block;text-transform:capitalize;margin-bottom:4px;color:var(--orange-deep)}' +
    '.tt-mark{border-radius:10px;padding:9px 12px;font-size:13.5px;background:var(--bg-soft,#f7f6f4);border:1px solid var(--line,#ddd)}' +
    '.tt-mark b{display:block;margin-bottom:3px}.tt-mark .hit{color:var(--green-deep,#2a7);font-weight:700}.tt-mark .miss{color:var(--orange-deep);font-weight:700}' +
    '@media(max-width:520px){.tt-cmp{grid-template-columns:1fr}}' +
    '.tt-dots{align-self:flex-start;color:var(--ink-soft,#777);font-size:18px;letter-spacing:2px;padding:0 6px}' +
    '.tt-think{font-size:13px;letter-spacing:0;font-weight:600;margin-right:2px}' +
    '.tt-caret{color:var(--orange);font-weight:400;margin-left:1px}' +
    '.tt-out{display:flex;flex-direction:column;gap:10px;margin-top:10px}' +
    '.tt-foot{display:flex;gap:8px;padding:12px;border-top:1px solid var(--line,#ddd);background:#fff}' +
    '.tt-foot input{flex:1;min-width:0;border:1px solid var(--line,#ddd);border-radius:10px;padding:11px 13px;font:inherit;font-size:14.5px}' +
    '.tt-foot input:focus{outline:2px solid var(--orange);outline-offset:0}' +
    '.tt-foot button{background:var(--orange);color:var(--tutor-on,#fff);border:0;border-radius:10px;padding:0 16px;font:700 14px inherit;font-family:inherit;cursor:pointer}' +
    '.tt-foot button:hover{background:var(--orange-deep)}' +
    '.tt-foot button[disabled]{opacity:.55;cursor:default}' +
    /* Floating panel on the module pages. Above the public assistant's z-index. */
    '.tt-fab{position:fixed;right:20px;bottom:20px;z-index:310;display:inline-flex;align-items:center;gap:9px;background:var(--orange);color:var(--tutor-on,#fff);border:0;border-radius:30px;padding:13px 19px;font:700 14px/1 inherit;font-family:inherit;cursor:pointer;box-shadow:0 16px 36px -14px rgba(0,0,0,.45)}' +
    '.tt-fab:hover{background:var(--orange-deep)}.tt-fab svg{width:19px;height:19px}' +
    '.tt-panel{position:fixed;right:20px;bottom:20px;z-index:311;width:min(420px,calc(100vw - 32px));height:min(620px,calc(100vh - 40px));display:none;flex-direction:column;box-shadow:0 34px 80px -28px rgba(0,0,0,.55)}' +
    '.tt-panel.open{display:flex}.tt-panel .tt{flex:1;border-radius:16px}' +
    '.tt-head{display:flex;align-items:center;gap:10px;padding:13px 15px;background:var(--dark,#222);color:#fff}' +
    '.tt-head b{font-size:15px}.tt-head small{display:block;font-size:11.5px;opacity:.75;font-weight:500}' +
    '.tt-head button{margin-left:auto;background:none;border:0;color:#fff;opacity:.8;font-size:22px;line-height:1;cursor:pointer}' +
    'body.tt-has-fab .aiw-btn{display:none!important}' +
    '@media(max-width:480px){.tt-panel{right:8px;bottom:8px;width:calc(100vw - 16px);height:calc(100vh - 16px)}.tt-fab{right:14px;bottom:14px}}';

  function addCss() {
    if (document.getElementById('tt-css')) return;
    var s = document.createElement('style'); s.id = 'tt-css';
    s.appendChild(document.createTextNode(CSS)); document.head.appendChild(s);
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = String(text);
    return n;
  }

  function sourceLink(src) {
    if (!src || !src.href) return null;
    var a = el('a', 'tt-src', 'From ' + src.label + ' →');
    a.href = src.href;
    return a;
  }

  /* The same stop words and stemmer as tutor_terms() in lib/tutor.php, so the
     marking of a quiz answer here agrees with the server's key words. */
  var STOP = ('a an the and or of to in on for is are was be it this that what which how why when who do does did i me my you your ' +
    'we with as at by from can could should would about explain tell mean means meaning define definition please some any there ' +
    'their them into its also not but if so than then more most give show help understand between difference').split(' ');
  function stem(w) {
    w = w.replace(/is(ation|ations|e|ed|es|ing)$/, 'iz$1');
    var R = [['ational', 'ate'], ['ization', 'ize'], ['ments', 'ment'], ['ness', ''], ['ings', ''], ['ing', ''], ['ies', 'y'],
             ['ied', 'y'], ['ed', ''], ['es', ''], ['s', '']];
    for (var i = 0; i < R.length; i++) {
      var s = R[i][0];
      if (w.length > s.length + 3 && w.slice(-s.length) === s) return w.slice(0, -s.length) + R[i][1];
    }
    return w;
  }
  function terms(s) {
    return (String(s).toLowerCase().match(/[\p{L}\p{N}]+/gu) || [])
      .filter(function (w) { return w.length >= 2 && STOP.indexOf(w) < 0; }).map(stem);
  }

  /** One conversation. opts: course, module, topic(fn or string), token(fn), first */
  function Chat(host, opts) {
    addCss();
    /* What the conversation remembers, in this browser tab only: the last real
       question (for "tell me more"), passages already shown, quiz points asked. */
    var state = { topic: '', module: '', lastq: '', seen: [], avoid: [], busy: false };
    var box = el('div', 'tt');
    var log = el('div', 'tt-log'); log.setAttribute('aria-live', 'polite');
    var form = el('form', 'tt-foot');
    var input = el('input'); input.type = 'text'; input.maxLength = 600;
    input.placeholder = 'Ask about your course…'; input.setAttribute('aria-label', 'Ask your tutor');
    var send = el('button', '', 'Ask'); send.type = 'submit';
    form.appendChild(input); form.appendChild(send);
    box.appendChild(log); box.appendChild(form); host.appendChild(box);

    function topicNow() {
      var t = typeof opts.topic === 'function' ? opts.topic() : (opts.topic || '');
      return t || state.topic;
    }
    function scroll() { log.scrollTop = log.scrollHeight; }

    function block(b) {
      var n;
      switch (b.type) {
        case 'p': return el('p', '', b.text);
        case 'list':
          n = el('ul'); (b.items || []).forEach(function (i) { n.appendChild(el('li', '', i)); }); return n;
        case 'quote':
          n = el('div', 'tt-q'); n.appendChild(el('div', '', b.text));
          var s = sourceLink(b.source); if (s) n.appendChild(s); return n;
        case 'check': return el('div', 'tt-check', b.text);
        case 'note': return el('div', 'tt-note', b.text);
        case 'define':
          n = el('div', 'tt-def'); n.appendChild(el('div', '', b.text));
          var sd = sourceLink(b.source); if (sd) n.appendChild(sd); return n;
        case 'compare':
          n = el('div', 'tt-cmp');
          (b.items || []).forEach(function (it) {
            var c = el('div'); c.appendChild(el('b', '', it.name)); c.appendChild(el('div', '', it.text));
            var sc = sourceLink(it.source); if (sc) c.appendChild(sc); n.appendChild(c);
          });
          return n;
        case 'topic':
          n = el('div', 'tt-topic'); n.appendChild(el('b', '', b.title));
          if (b.items && b.items.length) { var u = el('ul'); b.items.forEach(function (i) { u.appendChild(el('li', '', i)); }); n.appendChild(u); }
          if (b.key) n.appendChild(el('div', 'k', b.key));
          var s2 = sourceLink(b.source); if (s2) n.appendChild(s2); return n;
        case 'practice':
          n = el('div', 'tt-prac'); n.appendChild(el('strong', '', b.prompt));
          var ta = el('textarea'); ta.placeholder = 'Your answer, in your own words (stays on this screen)';
          ta.setAttribute('aria-label', 'Your answer'); n.appendChild(ta);
          var rv = el('button', 'tt-btn rv', 'Check my answer'); rv.type = 'button';
          ta.addEventListener('input', function () { rv.textContent = ta.value.trim() ? 'Check my answer' : 'Show what the material says'; });
          rv.textContent = 'Show what the material says';
          rv.addEventListener('click', function () {
            rv.remove(); ta.readOnly = true;
            var out = el('div', 'tt-out'); n.appendChild(out);
            /* Marked here, in the browser: the answer is never sent anywhere. */
            var mine = terms(ta.value), keys = b.keys || [];
            if (mine.length && keys.length) {
              var hit = [], miss = [];
              keys.forEach(function (k) { (mine.indexOf(k[1]) > -1 ? hit : miss).push(k[0]); });
              var mk = el('div', 'tt-mark');
              var score = hit.length / keys.length;
              mk.appendChild(el('b', '', score >= 0.6 ? 'Strong answer.' : score >= 0.3 ? 'Good start.' : 'Have another look at this one.'));
              var line = el('div');
              line.appendChild(document.createTextNode('You used ' + hit.length + ' of ' + keys.length + ' key ideas'));
              if (hit.length) { line.appendChild(document.createTextNode(': ')); line.appendChild(el('span', 'hit', hit.join(', '))); }
              line.appendChild(document.createTextNode('.'));
              mk.appendChild(line);
              if (miss.length) {
                var l2 = el('div'); l2.appendChild(document.createTextNode('Not mentioned: ')); l2.appendChild(el('span', 'miss', miss.join(', ')));
                mk.appendChild(l2);
              }
              mk.appendChild(el('div', 'tt-note', 'This counts key words, not meaning. Read the material below to be sure you have the idea right.'));
              out.appendChild(mk);
            }
            var a = el('div', 'tt-q ans'); a.appendChild(el('div', '', b.reveal));
            var s3 = sourceLink(b.source); if (s3) a.appendChild(s3);
            out.appendChild(a);
            if (!mine.length) out.appendChild(el('p', 'tt-check', 'Did you cover the main idea? If not, open the topic and read it again, then try another one.'));
            typeOut(out);
          });
          n.appendChild(rv); return n;
        case 'next':
          n = el('a', 'tt-next'); n.href = b.href; n.appendChild(el('b', '', b.title)); n.appendChild(el('span', '', b.sub)); return n;
      }
      return null;
    }

    /* ---- Writing it out live (16 Nov 2026, Sibusiso: "we must see it writing
       the answers"). The answer is already complete when it arrives; this
       writes it into the bubble word by word, block after block, with a cursor.
       Source links, buttons and the suggestion chips appear once their part is
       written. A long answer still finishes in about four seconds, a click
       (or the next question) finishes it at once, and anyone whose device asks
       for reduced motion gets it whole. Screen readers hear it once, when it is
       done: the log is aria-busy while it is being written. ---------------- */
    var typing = null;
    function reducedMotion() {
      try { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { return false; }
    }
    function typeOut(root, after) {
      if (typing) typing.finish();
      var LATE = '.tt-src,button,textarea,input';
      var parts = [], total = 0;
      Array.prototype.forEach.call(root.children, function (bl) {
        var late = Array.prototype.slice.call(bl.querySelectorAll(LATE));
        var texts = [];
        var w = document.createTreeWalker(bl, NodeFilter.SHOW_TEXT, null);
        for (var t = w.nextNode(); t; t = w.nextNode()) {
          if (late.some(function (l) { return l.contains(t); }) || !t.nodeValue.trim()) continue;
          texts.push({ node: t, full: t.nodeValue }); total += t.nodeValue.length;
        }
        parts.push({ el: bl, late: late, texts: texts });
      });
      if (reducedMotion() || !total) { if (after) after(); scroll(); return; }
      parts.forEach(function (p) {
        p.el.style.display = 'none';
        p.late.forEach(function (l) { l.style.display = 'none'; });
        p.texts.forEach(function (x) { x.node.nodeValue = ''; });
      });
      var caret = el('span', 'tt-caret', '▍'); caret.setAttribute('aria-hidden', 'true');
      log.setAttribute('aria-busy', 'true');
      var cps = Math.max(70, Math.min(700, total / 4));       // characters a second
      var pi = 0, ti = 0, pos = 0, last = 0, budget = 0, done = false;
      function showPart(p) { p.el.style.display = ''; }
      function endPart(p) { p.late.forEach(function (l) { l.style.display = ''; }); }
      function finish() {
        if (done) return; done = true;
        parts.forEach(function (p) { showPart(p); endPart(p); p.texts.forEach(function (x) { x.node.nodeValue = x.full; }); });
        caret.remove(); log.removeAttribute('aria-busy'); typing = null;
        if (after) after();
        scroll();
      }
      function frame(now) {
        if (done) return;
        var stick = log.scrollHeight - log.scrollTop - log.clientHeight < 90;
        budget += last ? (now - last) * cps / 1000 : 2; last = now;
        while (budget >= 1 && pi < parts.length) {
          var p = parts[pi];
          if (ti === 0 && pos === 0) showPart(p);
          var x = p.texts[ti];
          if (!x) { endPart(p); pi++; ti = 0; pos = 0; continue; }
          /* A word at a time: run on to the next space so words never break. */
          var next = Math.min(x.full.length, pos + Math.max(1, Math.floor(budget)));
          while (next < x.full.length && !/\s/.test(x.full.charAt(next))) next++;
          budget -= (next - pos); pos = next;
          x.node.nodeValue = x.full.slice(0, pos);
          x.node.parentNode.insertBefore(caret, x.node.nextSibling);
          if (pos >= x.full.length) { ti++; pos = 0; }
        }
        if (stick) log.scrollTop = log.scrollHeight;
        if (pi >= parts.length) finish(); else requestAnimationFrame(frame);
      }
      typing = { finish: finish };
      requestAnimationFrame(frame);
    }
    /* Browsers pause animation in a background tab; finish rather than wait. */
    document.addEventListener('visibilitychange', function () { if (document.hidden && typing) typing.finish(); });
    log.addEventListener('click', function (e) {
      /* A click on the answer being written finishes it; a click on a link or button still works. */
      if (typing && !e.target.closest('a,button,textarea')) typing.finish();
    });

    function bot(reply) {
      var m = el('div', 'tt-m bot');
      (reply.blocks || []).forEach(function (b) { var n = block(b); if (n) m.appendChild(n); });
      log.appendChild(m);
      scroll();
      typeOut(m, function () {
        if (!reply.chips || !reply.chips.length) return;
        var c = el('div', 'tt-chips');
        reply.chips.forEach(function (t) {
          var b = el('button', 'tt-chip', t); b.type = 'button';
          b.addEventListener('click', function () { ask(t === 'Another one' ? 'quiz me' : t); });
          c.appendChild(b);
        });
        log.appendChild(c);
      });
    }

    function ask(q) {
      q = String(q || '').trim();
      if (!q || state.busy) return;
      if (typing) typing.finish();
      state.busy = true; send.disabled = true;
      log.appendChild(el('div', 'tt-m me', q));
      var dots = el('div', 'tt-dots'); dots.appendChild(el('span', 'tt-think', 'Thinking')); dots.appendChild(document.createTextNode(' •••')); log.appendChild(dots); scroll();
      var body = new URLSearchParams();
      body.set('q', q); body.set('course', opts.course); body.set('module', opts.module || state.module || '');
      body.set('topic', topicNow()); body.set('_token', opts.token());
      body.set('lastq', state.lastq); body.set('seen', state.seen.join(',')); body.set('avoid', state.avoid.join(','));
      fetch('tutor.php', { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' }, body: body })
        .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Something went wrong. Try again.' }; }); })
        .then(function (j) {
          dots.remove();
          if (!j.ok) { bot({ blocks: [{ type: 'p', text: j.message || 'Something went wrong. Try again.' }] }); return; }
          if (j.focus && (!opts.module || j.focus.module === opts.module)) {
            if (!opts.module && j.focus.module) state.module = j.focus.module;
            state.topic = j.focus.topic || '';
          }
          if (j.lastq) state.lastq = j.lastq;
          if (j.shown) state.seen = state.seen.concat(j.shown).slice(-200);
          if (j.asked != null) state.avoid = state.avoid.concat([j.asked]).slice(-200);
          if (j.reset_avoid) state.avoid = [];
          bot(j);
        })
        .catch(function () { dots.remove(); bot({ blocks: [{ type: 'p', text: "I couldn't reach the academy. Check your connection and try again." }] }); })
        .then(function () { state.busy = false; send.disabled = false; });
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); var q = input.value; input.value = ''; ask(q); });
    return { ask: ask, input: input, greet: function () { ask('hello'); } };
  }

  var SPSTutor = {
    /** tutor.php */
    inline: function (host) {
      var c = Chat(host, { course: host.dataset.course, module: host.dataset.module,
                           token: function () { return host.dataset.token; } });
      c.greet();
      return c;
    },

    /** The module pages: a button and a panel, for a signed-in learner. */
    floating: function (session) {
      var qs = new URLSearchParams(location.search);
      var course = qs.get('c') || 'project-management';
      var module = (qs.get('m') || '').toUpperCase();
      if (!session || !module) return;
      if ((session.courses || []).map(function (c) { return c.slug || c; }).indexOf(course) < 0) return;
      addCss();
      document.body.classList.add('tt-has-fab');
      var fab = el('button', 'tt-fab'); fab.type = 'button';
      fab.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/></svg>';
      fab.appendChild(el('span', '', 'Ask your tutor'));
      var panel = el('div', 'tt-panel'); panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-label', 'Study tutor');
      var head = el('div', 'tt-head');
      var t = el('div'); t.appendChild(el('b', '', 'Study tutor')); t.appendChild(el('small', '', module + ' · answers from your course material'));
      var x = el('button', '', '×'); x.type = 'button'; x.setAttribute('aria-label', 'Close');
      head.appendChild(t); head.appendChild(x);
      var inner = el('div'); inner.style.cssText = 'flex:1;display:flex;flex-direction:column;min-height:0';
      panel.appendChild(head); panel.appendChild(inner);
      document.body.appendChild(fab); document.body.appendChild(panel);
      var chat = null;
      fab.addEventListener('click', function () {
        panel.classList.add('open'); fab.style.display = 'none';
        if (!chat) {
          chat = Chat(inner, { course: course, module: module,
            topic: function () { return decodeURIComponent((location.hash || '').slice(1)).toUpperCase(); },
            token: function () { var s = window.SPSAccount && window.SPSAccount.get && window.SPSAccount.get(); return (s && s.token) || session.token || ''; } });
          inner.firstChild.style.borderRadius = '0 0 16px 16px';
          chat.greet();
        }
        chat.input.focus();
      });
      x.addEventListener('click', function () { panel.classList.remove('open'); fab.style.display = ''; });
    }
  };
  window.SPSTutor = SPSTutor;

  var host = document.getElementById('tutor');
  if (host && host.dataset.course) SPSTutor.inline(host);
})();
