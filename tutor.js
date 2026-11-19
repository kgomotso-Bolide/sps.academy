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
    '@media(max-width:480px){.tt-panel{right:8px;bottom:8px;width:calc(100vw - 16px);height:calc(100vh - 16px)}.tt-fab{right:14px;bottom:14px}}' +
    /* Voice (19 Nov 2026). No animation: states are colour and words, not pulses. */
    '.tt-foot .tt-mic{flex:0 0 46px;padding:0;display:grid;place-items:center;background:#fff;color:var(--ink,#222);border:1.5px solid var(--line,#ddd)}' +
    '.tt-foot .tt-mic:hover{background:#fff;border-color:var(--orange);color:var(--orange-deep)}' +
    '.tt-foot .tt-mic.on{background:#c62828;border-color:#c62828;color:#fff}' +
    '.tt-mic svg,.tt-talk svg,.tt-say svg{width:19px;height:19px}' +
    '.tt-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:9px 12px;border-top:1px solid var(--line,#ddd);background:#fff}' +
    '.tt-talk{display:inline-flex;align-items:center;gap:8px;border:1.5px solid var(--orange);background:#fff;color:var(--orange-deep);border-radius:30px;padding:7px 14px;font:700 13.5px/1.2 inherit;font-family:inherit;cursor:pointer}' +
    '.tt-talk:hover{background:color-mix(in srgb,var(--orange) 8%,#fff)}' +
    '.tt-talk.on{background:var(--orange);color:var(--tutor-on,#fff)}' +
    '.tt-aloud{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--ink-soft,#555);cursor:pointer;margin-left:auto}' +
    '.tt-aloud input{width:16px;height:16px;margin:0;accent-color:var(--orange)}' +
    '.tt-status{flex:1 1 100%;font-size:13px;font-weight:700;color:var(--ink,#222)}.tt-status:empty{display:none}' +
    '.tt-status.listen{color:#c62828}.tt-status.speak{color:var(--green-deep,#2a7)}' +
    '.tt-say{align-self:flex-start;display:inline-flex;align-items:center;gap:6px;margin-top:2px;background:none;border:0;padding:4px 0;color:var(--orange-deep);font:700 12.5px/1 inherit;font-family:inherit;cursor:pointer}' +
    '.tt-say:hover{text-decoration:underline}' +
    '.tt-consent{align-self:stretch;border:1px solid var(--line,#ddd);background:var(--bg-soft,#f7f6f4);border-radius:12px;padding:12px 14px;font-size:13.5px;line-height:1.5}' +
    '.tt-consent p{margin:0 0 10px}.tt-consent div{display:flex;gap:8px;flex-wrap:wrap}' +
    '.tt-consent .tt-yes{background:var(--orange);color:var(--tutor-on,#fff);border-color:var(--orange)}' +
    '.tt-m.me.voice::before{content:"\\1F399  ";}';

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

  /* ---- Voice helpers (19 Nov 2026) ----------------------------------------- */

  function store(k, v) {
    try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; }
    return null;
  }
  var ICON = {
    mic:     '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M8 21h8"/>',
    talk:    '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/>',
    stop:    '<rect x="6" y="6" width="12" height="12" rx="2"/>',
    speaker: '<path d="M11 5L6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>'
  };
  function icon(p) {
    var t = document.createElement('template');
    t.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
    return t.content.firstChild;
  }

  /* What an answer says out loud: its text, without source links, buttons or
     a quiz's hidden answer (that is only built when the learner reveals it). */
  /* Listening to every extra passage is too long, so after "More from your
     material" the passages stay on screen only (with one spoken pointer to
     them), and the closing "Check yourself" question is still read. */
  function spoken(m) {
    var skip = '.tt-src,button,textarea,input,.tt-say,.tt-note';
    var out = [], more = false, allow = 0;
    Array.prototype.forEach.call(m.children, function (b) {
      if (b.matches(skip)) return;
      if (/^More from your material/i.test((b.textContent || '').trim())) {
        more = true;
        /* Nothing said yet: read the first passage. Otherwise point to the screen. */
        allow = out.length ? 0 : 1;
        out.push(out.length ? 'There is more from your material on screen.' : 'Here is what your material says.');
        return;
      }
      if (more && b.matches('.tt-q')) {
        if (allow < 1) return;
        allow--;
      } else if (more) {
        more = false;
      }
      var w = document.createTreeWalker(b, NodeFilter.SHOW_TEXT, null), bits = [];
      for (var t = w.nextNode(); t; t = w.nextNode()) {
        if (t.parentNode.closest(skip)) continue;
        if (t.nodeValue.trim()) bits.push(t.nodeValue.trim());
      }
      if (bits.length) out.push(bits.join(' '));
    });
    return out.join('. ').replace(/\s-\s/g, ', ').replace(/([.!?:])\s*\./g, '$1').replace(/\s+/g, ' ').trim();
  }

  /* One voice for the page: the tutor and the topic reader never talk over
     each other. lessons.js exposes window.SPSReadAloud for the same reason. */
  var VOICE = null;
  function voice() {
    if (VOICE) return VOICE;
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    var synth = window.speechSynthesis;
    var canSpeak = !!(synth && window.SpeechSynthesisUtterance);
    var picked = null, run = 0;
    function pick() {
      var vs = (synth.getVoices() || []).filter(function (v) { return /^en/i.test(v.lang); });
      var rank = function (v) {
        var s = 0;
        if (/en[-_]ZA/i.test(v.lang)) s += 40; else if (/en[-_]GB/i.test(v.lang)) s += 30; else if (/en[-_]US/i.test(v.lang)) s += 15;
        if (/natural|online|neural|google/i.test(v.name)) s += 25;
        return s;
      };
      vs.sort(function (a, b) { return rank(b) - rank(a); });
      return vs[0] || null;
    }
    if (canSpeak && synth.onvoiceschanged !== undefined) {
      synth.addEventListener('voiceschanged', function () { picked = pick(); });
    }
    VOICE = {
      hear: !!SR,
      speak: canSpeak,
      recognizer: function () {
        var r = new SR();
        r.lang = 'en-ZA';
        r.interimResults = true;
        r.continuous = false;
        r.maxAlternatives = 1;
        return r;
      },
      /* iOS only lets a page speak after a tap; a silent utterance on the tap opens it. */
      unlock: function () {
        if (!canSpeak) return;
        try { var u = new SpeechSynthesisUtterance(' '); u.volume = 0; synth.speak(u); } catch (e) {}
      },
      hush: function () {
        run++;
        if (canSpeak) synth.cancel();
      },
      /* Sentence chunks of ~220 characters: long utterances stall in some browsers. */
      say: function (text, done) {
        if (!canSpeak || !text) { if (done) done(); return; }
        if (window.SPSReadAloud) window.SPSReadAloud.stop();
        this.hush();
        var my = run;
        var s = text.match(/[^.!?]+[.!?]+["')\]]*\s*|[^.!?]+$/g) || [text], chunks = [], cur = '';
        s.forEach(function (x) { if ((cur + x).length > 220 && cur) { chunks.push(cur.trim()); cur = ''; } cur += x; });
        if (cur.trim()) chunks.push(cur.trim());
        if (!picked) picked = pick();
        var i = 0;
        (function next() {
          if (my !== run) return;
          if (i >= chunks.length) { if (done) done(); return; }
          var u = new SpeechSynthesisUtterance(chunks[i++]);
          if (picked) { u.voice = picked; u.lang = picked.lang; } else u.lang = 'en-ZA';
          u.rate = +(store('read:rate') || 1) || 1;
          u.onend = function () { if (my === run) setTimeout(next, 0); };
          u.onerror = function (e) {
            if (my !== run || e.error === 'interrupted' || e.error === 'canceled') return;
            run++; if (done) done();          // cannot speak here: carry on silently with the text
          };
          synth.speak(u);
        })();
      }
    };
    window.SPSTutorVoice = { stop: function () { VOICE.hush(); } };
    window.addEventListener('pagehide', function () { if (canSpeak) synth.cancel(); });
    return VOICE;
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
    var V = voice();
    var mic = null, tools = null, talkBtn = null, aloud = null, status = null;
    if (V.hear) {
      mic = el('button', 'tt-mic'); mic.type = 'button';
      mic.setAttribute('aria-label', 'Ask by voice'); mic.title = 'Ask by voice';
      mic.appendChild(icon(ICON.mic));
      form.appendChild(mic);
    }
    form.appendChild(input); form.appendChild(send);
    if (V.hear || V.speak) {
      tools = el('div', 'tt-tools');
      if (V.hear) {
        talkBtn = el('button', 'tt-talk'); talkBtn.type = 'button';
        tools.appendChild(talkBtn);
      }
      if (V.speak) {
        var al = el('label', 'tt-aloud');
        aloud = el('input'); aloud.type = 'checkbox';
        aloud.checked = store('tutor:aloud') === '1';
        aloud.addEventListener('change', function () {
          store('tutor:aloud', aloud.checked ? '1' : '0');
          if (!aloud.checked && !talking) V.hush();
        });
        al.appendChild(aloud); al.appendChild(document.createTextNode('Read answers aloud'));
        tools.appendChild(al);
      }
      status = el('div', 'tt-status'); status.setAttribute('role', 'status');
      tools.appendChild(status);
    }
    box.appendChild(log);
    if (tools) box.appendChild(tools);
    box.appendChild(form); host.appendChild(box);

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

    function bot(reply, opt) {
      opt = opt || {};
      var m = el('div', 'tt-m bot');
      (reply.blocks || []).forEach(function (b) { var n = block(b); if (n) m.appendChild(n); });
      /* Read before typeOut blanks the text it is about to write out. */
      var said = V.speak ? spoken(m) : '';
      if (said && !opt.quiet) {
        var sb = el('button', 'tt-say'); sb.type = 'button';
        sb.appendChild(icon(ICON.speaker)); sb.appendChild(el('span', '', 'Listen'));
        sb.addEventListener('click', function () { stopListening(); say(said); });
        m.appendChild(sb);
      }
      log.appendChild(m);
      scroll();
      /* Spoken aloud when it was asked aloud, when talking, or when the learner
         has switched on "Read answers aloud". Always written out as well. */
      if (said && !opt.quiet && (opt.voice || talking || (aloud && aloud.checked))) {
        say(said, talking ? function () { if (talking) listen(); } : null);
      } else if (talking && !opt.quiet) {
        listen();
      }
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

    function ask(q, byVoice) {
      q = String(q || '').trim().slice(0, 600);
      if (!q || state.busy) return;
      if (typing) typing.finish();
      V.hush();
      state.busy = true; send.disabled = true;
      log.appendChild(el('div', 'tt-m me' + (byVoice ? ' voice' : ''), q));
      if (talking) setStatus('Thinking…', '');
      var dots = el('div', 'tt-dots'); dots.appendChild(el('span', 'tt-think', 'Thinking')); dots.appendChild(document.createTextNode(' •••')); log.appendChild(dots); scroll();
      var body = new URLSearchParams();
      body.set('q', q); body.set('course', opts.course); body.set('module', opts.module || state.module || '');
      body.set('topic', topicNow()); body.set('_token', opts.token());
      body.set('lastq', state.lastq); body.set('seen', state.seen.join(',')); body.set('avoid', state.avoid.join(','));
      fetch('tutor.php', { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' }, body: body })
        .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Something went wrong. Try again.' }; }); })
        .then(function (j) {
          dots.remove();
          if (!j.ok) { bot({ blocks: [{ type: 'p', text: j.message || 'Something went wrong. Try again.' }] }, { voice: byVoice }); return; }
          if (j.focus && (!opts.module || j.focus.module === opts.module)) {
            if (!opts.module && j.focus.module) state.module = j.focus.module;
            state.topic = j.focus.topic || '';
          }
          if (j.lastq) state.lastq = j.lastq;
          if (j.shown) state.seen = state.seen.concat(j.shown).slice(-200);
          if (j.asked != null) state.avoid = state.avoid.concat([j.asked]).slice(-200);
          if (j.reset_avoid) state.avoid = [];
          bot(j, { voice: byVoice, quiet: q === 'hello' && !byVoice });
        })
        .catch(function () { dots.remove(); bot({ blocks: [{ type: 'p', text: "I couldn't reach the academy. Check your connection and try again." }] }, { voice: byVoice }); })
        .then(function () { state.busy = false; send.disabled = false; });
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); var q = input.value; input.value = ''; ask(q); });

    /* ---- Voice: speak a question, hear the answer, talk back and forth -----
       Hearing uses the browser's speech recognition. In Chrome that is Google's
       service, in Edge Microsoft's, in Safari Apple's: the audio goes to them to
       be turned into words, and only the words come here — the academy never
       receives or keeps a recording. That is said plainly the first time the
       microphone is used, and nothing listens until the learner agrees.
       Speaking uses the device's own voice and sends nothing anywhere. */
    var talking = false, rec = null, misses = 0;

    function setStatus(text, kind) {
      if (!status) return;
      status.textContent = text || '';
      status.className = 'tt-status' + (kind ? ' ' + kind : '');
    }
    function paintTalk() {
      if (!talkBtn) return;
      talkBtn.textContent = '';
      talkBtn.appendChild(icon(talking ? ICON.stop : ICON.talk));
      talkBtn.appendChild(el('span', '', talking ? 'End conversation' : 'Talk with your tutor'));
      talkBtn.classList.toggle('on', talking);
      talkBtn.setAttribute('aria-pressed', talking ? 'true' : 'false');
    }
    paintTalk();

    function say(text, after) {
      setStatus(talking ? 'Speaking… tap the microphone to interrupt' : '', 'speak');
      V.say(text, function () {
        if (!talking) setStatus('', '');
        if (after) after();
      });
    }

    function consent(go) {
      if (store('tutor:voice-ok') === '1') { go(); return; }
      if (log.querySelector('.tt-consent')) return;
      var c = el('div', 'tt-consent');
      c.appendChild(el('p', '', 'To understand you, your browser sends your voice to its speech service ' +
        '(Google in Chrome, Microsoft in Edge, Apple in Safari), which turns it into words. Only the words ' +
        'come to the academy, the same as if you had typed them. Nothing is recorded or kept here.'));
      var row = el('div');
      var yes = el('button', 'tt-btn tt-yes', 'Use my microphone'); yes.type = 'button';
      var no = el('button', 'tt-btn', 'Not now'); no.type = 'button';
      yes.addEventListener('click', function () { store('tutor:voice-ok', '1'); c.remove(); go(); });
      no.addEventListener('click', function () { c.remove(); });
      row.appendChild(yes); row.appendChild(no); c.appendChild(row);
      log.appendChild(c); scroll();
    }

    function stopListening() {
      if (rec) { try { rec.abort(); } catch (e) {} rec = null; }
      if (mic) { mic.classList.remove('on'); mic.setAttribute('aria-label', 'Ask by voice'); }
    }

    function listen() {
      if (!V.hear || state.busy) return;
      V.hush();
      stopListening();
      var r = V.recognizer();
      rec = r;
      mic.classList.add('on'); mic.setAttribute('aria-label', 'Stop listening');
      input.value = '';
      input.placeholder = 'Listening… speak now';
      setStatus(talking ? 'Listening… speak now' : 'Listening…', 'listen');
      /* The words appear in the box as they are heard, so the learner can see
         what was understood; the question is sent when they stop speaking. */
      r.onresult = function (e) {
        var txt = '';
        for (var i = 0; i < e.results.length; i++) txt += e.results[i][0].transcript;
        input.value = txt.trim();
      };
      r.onerror = function (e) {
        if (rec !== r) return;
        if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
          endTalk();
          bot({ blocks: [{ type: 'p', text: 'The microphone is blocked for this site. Allow it in your browser’s site settings, then try again — or type your question.' }] }, { quiet: true });
        } else if (e.error === 'network') {
          endTalk();
          bot({ blocks: [{ type: 'p', text: 'Voice needs an internet connection to understand you. You can still type your question.' }] }, { quiet: true });
        } else if (e.error === 'audio-capture') {
          endTalk();
          bot({ blocks: [{ type: 'p', text: 'No microphone was found on this device. You can type your question instead.' }] }, { quiet: true });
        }
        /* no-speech and aborted are handled in onend. */
      };
      r.onend = function () {
        if (rec !== r) return;
        rec = null;
        mic.classList.remove('on'); mic.setAttribute('aria-label', 'Ask by voice');
        input.placeholder = 'Ask about your course…';
        var q = (input.value || '').trim();
        if (q) {
          misses = 0;
          input.value = '';
          if (talking && /^(stop|end|goodbye|good bye|bye|that'?s all|no more|thank you,? (bye|goodbye))\b/i.test(q)) {
            endTalk(); say('Goodbye. Good luck with your studies.');
            return;
          }
          ask(q, true);
          return;
        }
        if (talking && ++misses < 2) { listen(); return; }
        if (talking) { endTalk(); setStatus('Conversation ended — I didn’t hear anything.', ''); return; }
        setStatus('', '');
      };
      try { r.start(); } catch (e) { stopListening(); setStatus('', ''); }
    }

    function endTalk() {
      talking = false; misses = 0;
      stopListening(); V.hush();
      paintTalk(); setStatus('', '');
      input.placeholder = 'Ask about your course…';
    }

    if (mic) mic.addEventListener('click', function () {
      if (rec) { try { rec.stop(); } catch (e) {} return; }      // stop now; what was heard is sent
      consent(function () { V.hush(); listen(); });
    });
    if (talkBtn) talkBtn.addEventListener('click', function () {
      if (talking) { endTalk(); return; }
      consent(function () {
        talking = true; misses = 0; paintTalk();
        V.unlock();
        say('I’m listening. Ask me anything about your course.', function () { if (talking) listen(); });
      });
    });

    return { ask: ask, input: input, greet: function () { ask('hello'); }, end: endTalk };
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
      x.addEventListener('click', function () {
        if (chat) chat.end();                 // closing the panel ends any conversation
        panel.classList.remove('open'); fab.style.display = '';
      });
    }
  };
  window.SPSTutor = SPSTutor;

  var host = document.getElementById('tutor');
  if (host && host.dataset.course) SPSTutor.inline(host);
})();
