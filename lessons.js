/* The written material for a topic, on the module page.
 *
 * Same manners as materials.js and quiz-widget.js: it asks the server, and if
 * the answer is "not signed in", "not enrolled", or "nothing written yet" it
 * does nothing at all and leaves the page exactly as the curriculum rendered
 * it. A module with no written material must look deliberate, not broken —
 * most of them have none yet.
 *
 * WHY THIS IS PER TOPIC AND NOT PER AREA (changed 8 Sep 2026)
 * ----------------------------------------------------------
 * The Learner Guides are written topic by topic, not area by area, so the
 * material opens as one block per topic beneath the syllabus list. Where a
 * topic DOES have its material split into named parts, each part gets a
 * sub-heading inside the same block.
 *
 * THE READER (19 Nov 2026, Sibusiso: "the text is long, make it easy to read",
 * and "a voice reads the course module")
 * ------------------------------------------------------------------------
 * - The topic is cut into short pages (about 300 words, broken at paragraph
 *   ends and before headings) with Previous / Next, a page count and a bar.
 *   A wall of 1,000+ words is where people stop; a page is a sitting.
 * - Lines that are plainly headings in the source ("Project management career
 *   and qualifications" — short, no full stop) are shown as headings. The text
 *   itself is not changed; only how it is set.
 * - Bigger, darker text on a white page, a comfortable line length, and A− / A+
 *   for people who need it larger. The size and the page reached are remembered
 *   in this browser (a convenience: if storage is blocked it simply starts at
 *   page 1, normal size).
 * - Listen: the browser's own voice (speechSynthesis) reads the page aloud,
 *   paragraph by paragraph, with the paragraph being read highlighted, and turns
 *   the pages itself. "Listen to this module" reads every topic in order. No
 *   server, no cost, and no recording leaves the device: speech synthesis is
 *   output only. Where a browser has no voice the Listen buttons are not shown.
 * - No movement: Kgomotso asked for no animation on the sites (3 Sep 2026).
 *
 * The HTML comes from the server already escaped (see lib/sections.php on why
 * the stored form is plain text). This file does not build markup out of
 * anything a person typed; it only places what it was given, and sets text
 * through textContent where it makes its own.
 */
(function () {
  'use strict';

  var params = new URLSearchParams(location.search);
  var moduleCode = params.get('m');
  if (!moduleCode) return;

  /* From course-context.js since 16 Sep 2026 — see the note there. */
  var COURSE = window.ACADEMY_COURSE || 'project-management';
  var SITE = location.pathname.split('/')[1] || 'root';
  var PAGE_WORDS = 300;

  /* reader.css travels with this file, at the same version. */
  (function () {
    var me = document.querySelector('script[src*="lessons.js"]');
    var v = me ? (me.getAttribute('src').split('?')[1] || '') : '';
    var l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = 'reader.css' + (v ? '?' + v : '');
    document.head.appendChild(l);
  })();

  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  fetch('lessons.php?course=' + encodeURIComponent(COURSE) +
        '&module=' + encodeURIComponent(moduleCode), { credentials: 'same-origin' })
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (data) {
      if (!data || !data.in || !data.enrolled || !data.sections) return;
      apply(data.sections);
    })
    .catch(function () { /* silent, on purpose — see the note above */ });

  /* 200 wpm is the usual figure for studying rather than skimming. */
  function readingTime(words, wpm) {
    var m = Math.max(1, Math.round(words / (wpm || 200)));
    if (m < 60) return 'about ' + m + ' min';
    var h = Math.floor(m / 60), r = Math.round((m % 60) / 5) * 5;
    return 'about ' + h + ' hr' + (r ? ' ' + r + ' min' : '');
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function icon(p) {
    var s = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
    var t = document.createElement('template'); t.innerHTML = s; return t.content.firstChild;
  }
  var I = {
    book:  '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM8 7h7"/>',
    play:  '<path d="M7 4l13 8-13 8z"/>',
    pause: '<path d="M8 4v16M16 4v16"/>',
    stop:  '<rect x="6" y="6" width="12" height="12" rx="1"/>',
    ear:   '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/>',
    left:  '<path d="M15 18l-6-6 6-6"/>',
    right: '<path d="M9 18l6-6-6-6"/>',
    check: '<path d="M5 12l5 5 9-10"/>',
    x:     '<path d="M6 6l12 12M18 6L6 18"/>'
  };
  function words(s) { return (s.match(/\S+/g) || []).length; }

  /* ---- Turning the stored material into blocks, then pages ------------------ */

  /* A paragraph that is really a heading: one line, short, no closing
     punctuation, starts with a capital or a number. Errs on the side of
     leaving a paragraph a paragraph. */
  function headingLike(p) {
    if (p.querySelector('br')) return false;
    var t = (p.textContent || '').trim();
    if (!t || t.length > 80 || words(t) > 9) return false;
    if (/[.,;:!?…]["')\]]?$/.test(t)) return false;
    /* "…is outlined below" introduces a list; it is a sentence, not a title. */
    if (/\b(below|following|follows|include|includes|are|is)$/i.test(t)) return false;
    return /^[A-Z0-9]/.test(t);
  }

  function toBlocks(parts) {
    var out = [];
    parts.forEach(function (p) {
      if (p.title) out.push({ h: true, text: p.title, w: words(p.title) });
      var box = document.createElement('div');
      box.innerHTML = p.html;              // server-escaped; see the header note
      Array.prototype.forEach.call(box.children, function (c) {
        if (c.tagName === 'P') {
          if (headingLike(c)) out.push({ h: true, text: c.textContent.trim(), w: words(c.textContent) });
          else out.push({ node: c, w: words(c.textContent) });
        } else if (c.tagName === 'UL' || c.tagName === 'OL') {
          out.push({ node: c, w: words(c.textContent) });
        } else {
          out.push({ node: c, w: words(c.textContent || '') });
        }
      });
    });
    return out;
  }

  function paginate(blocks) {
    var pages = [], cur = [], n = 0;
    blocks.forEach(function (b) {
      var full = n + b.w > PAGE_WORDS && n >= 120;
      var niceBreak = b.h && n >= 170;
      if (cur.length && (full || niceBreak)) { pages.push(cur); cur = []; n = 0; }
      cur.push(b); n += b.w;
    });
    if (cur.length) pages.push(cur);
    /* Never end a page on a heading: carry it over to the page it introduces. */
    for (var i = 0; i < pages.length - 1; i++) {
      var last = pages[i][pages[i].length - 1];
      if (last.h && pages[i].length > 1) pages[i + 1].unshift(pages[i].pop());
    }
    /* A last page of a few lines reads as a mistake; fold it back. */
    if (pages.length > 1) {
      var tail = pages[pages.length - 1].reduce(function (a, b) { return a + b.w; }, 0);
      if (tail < 60) { var t = pages.pop(); pages[pages.length - 1] = pages[pages.length - 1].concat(t); }
    }
    return pages;
  }

  /* ---- Text size, shared by every reader on the page ----------------------- */

  var SIZES = [16, 17.5, 19, 21, 23];
  var sizeAt = +(store.get('read:size') || 1);
  if (!(sizeAt >= 0 && sizeAt < SIZES.length)) sizeAt = 1;
  function applySize() {
    document.documentElement.style.setProperty('--rd-size', SIZES[sizeAt] + 'px');
    document.querySelectorAll('.rd-smaller').forEach(function (b) { b.disabled = sizeAt === 0; });
    document.querySelectorAll('.rd-bigger').forEach(function (b) { b.disabled = sizeAt === SIZES.length - 1; });
  }

  /* ---- The reader ---------------------------------------------------------- */

  var readers = [];

  function apply(sections) {
    document.querySelectorAll('#m-topics .module').forEach(function (card) {
      var meta = card.querySelector('.meta');
      if (!meta) return;
      var code = (meta.textContent || '').split('·')[0].trim();
      var areas = sections[code];
      if (!areas) return;
      var keys = Object.keys(areas).sort(function (a, b) { return (+a) - (+b); });
      if (!keys.length) return;
      var total = 0;
      keys.forEach(function (k) { total += (areas[k].words || 0); });
      readers.push(new Reader(card, code, keys.map(function (k) { return areas[k]; }), total));
    });
    if (!readers.length) return;
    applySize();
    if (Speaker.ok) moduleBar();
  }

  function Reader(card, code, parts, total) {
    var self = this;
    this.card = card;
    this.code = code;
    var h4 = card.querySelector('.module-head h4');
    this.title = h4 ? h4.textContent : code;
    this.pages = paginate(toBlocks(parts));
    this.total = total;
    this.page = 0;
    this.key = 'read:' + SITE + ':' + COURSE + ':' + code;
    var saved = +(store.get(this.key) || 0);
    if (saved > 0 && saved < this.pages.length) this.page = saved;

    var id = 'read-' + code;

    /* The row that opens it. */
    var open = el('button', 'read-open');
    open.type = 'button';
    open.setAttribute('aria-expanded', 'false');
    open.setAttribute('aria-controls', id);
    var ico = el('span', 'rd-ico'); ico.appendChild(icon(I.book));
    var txt = el('span', 'rd-open-txt');
    txt.appendChild(el('strong', 'read-label', 'Read this topic'));
    var np = this.pages.length;
    txt.appendChild(el('span', 'read-meta',
      total.toLocaleString() + ' words · ' + readingTime(total) +
      (np > 1 ? ' · ' + np + ' short pages' : '')));
    this.resume = el('span', 'rd-resume');
    open.appendChild(ico); open.appendChild(txt); open.appendChild(this.resume);
    var go = el('span', 'rd-open-go'); go.appendChild(icon(I.right)); open.appendChild(go);
    this.open = open;

    /* The reader itself. */
    var box = el('div', 'reader');
    box.id = id;
    box.hidden = true;

    var bar = el('div', 'rd-bar');
    if (Speaker.ok) {
      this.listen = el('button', 'rd-btn rd-listen');
      this.listen.type = 'button';
      this.listen.addEventListener('click', function () { Speaker.toggle(self, 'topic'); });
      bar.appendChild(this.listen);
    }
    this.count = el('span', 'rd-count');
    bar.appendChild(this.count);
    bar.appendChild(el('span', 'rd-gap'));
    var size = el('span', 'rd-size');
    var sm = el('button', 'rd-btn rd-smaller', 'A−'); sm.type = 'button'; sm.setAttribute('aria-label', 'Smaller text');
    var bg = el('button', 'rd-btn rd-bigger', 'A+'); bg.type = 'button'; bg.setAttribute('aria-label', 'Bigger text');
    sm.addEventListener('click', function () { if (sizeAt > 0) { sizeAt--; store.set('read:size', sizeAt); applySize(); } });
    bg.addEventListener('click', function () { if (sizeAt < SIZES.length - 1) { sizeAt++; store.set('read:size', sizeAt); applySize(); } });
    size.appendChild(sm); size.appendChild(bg);
    bar.appendChild(size);
    var close = el('button', 'rd-btn rd-close'); close.type = 'button';
    close.setAttribute('aria-label', 'Close the reading');
    close.appendChild(icon(I.x));
    close.addEventListener('click', function () { self.toggle(false); open.focus(); });
    bar.appendChild(close);
    box.appendChild(bar);

    this.prog = el('div', 'rd-progress');
    this.progFill = el('i');
    this.prog.appendChild(this.progFill);
    box.appendChild(this.prog);

    this.sheet = el('article', 'rd-page');
    this.sheet.tabIndex = -1;
    this.sheet.setAttribute('aria-live', 'polite');
    box.appendChild(this.sheet);

    var foot = el('div', 'rd-foot');
    this.prev = el('button', 'rd-btn rd-prev');
    this.prev.type = 'button';
    this.prev.appendChild(icon(I.left)); this.prev.appendChild(el('span', '', 'Previous'));
    this.prev.addEventListener('click', function () { self.go(self.page - 1); });
    this.dots = el('span', 'rd-dots');
    this.next = el('button', 'rd-btn rd-next');
    this.next.type = 'button';
    this.next.addEventListener('click', function () { self.forward(); });
    foot.appendChild(this.prev); foot.appendChild(this.dots); foot.appendChild(this.next);
    box.appendChild(foot);
    this.box = box;

    open.addEventListener('click', function () { self.toggle(box.hidden); });

    /* After the syllabus list, before the "mark this topic done" tick — the
       order somebody actually works in: see what it covers, read it, tick it. */
    var tick = card.querySelector('.topic-tick');
    if (tick) { card.insertBefore(open, tick); card.insertBefore(box, tick); }
    else { card.appendChild(open); card.appendChild(box); }

    this.render();
  }

  Reader.prototype.toggle = function (show) {
    this.box.hidden = !show;
    this.open.hidden = show;
    this.open.setAttribute('aria-expanded', show ? 'true' : 'false');
    if (!show && Speaker.reader === this) Speaker.stop();
    if (show) this.top();
  };

  Reader.prototype.top = function () {
    var r = this.box.getBoundingClientRect();
    if (r.top < 60 || r.top > window.innerHeight * 0.6) this.box.scrollIntoView({ block: 'start' });
  };

  Reader.prototype.tick = function () { return this.card.querySelector('.topic-tick input'); };
  Reader.prototype.nextLink = function () { return this.card.querySelector('.topic-next a'); };

  Reader.prototype.forward = function () {
    if (this.page < this.pages.length - 1) { this.go(this.page + 1); return; }
    var t = this.tick();
    if (t && !t.checked) { t.click(); this.render(); return; }
    var n = this.nextLink();
    if (n) location.href = n.getAttribute('href');
  };

  Reader.prototype.go = function (n, fromSpeaker) {
    if (n < 0 || n >= this.pages.length) return;
    this.page = n;
    store.set(this.key, String(n));
    this.render();
    this.top();
    if (!fromSpeaker) {
      if (Speaker.reader === this && Speaker.playing) Speaker.restart();
      this.sheet.focus({ preventScroll: true });
    }
  };

  Reader.prototype.render = function () {
    var self = this, page = this.pages[this.page], last = this.page === this.pages.length - 1;
    this.sheet.textContent = '';
    page.forEach(function (b) {
      var n;
      if (b.h) n = el('h4', 'rd-h', b.text);
      else n = b.node.cloneNode(true);
      self.sheet.appendChild(n);
    });
    var np = this.pages.length;
    this.count.textContent = np > 1 ? 'Page ' + (this.page + 1) + ' of ' + np : 'One page';
    this.progFill.style.width = Math.round((this.page + 1) / np * 100) + '%';
    this.prog.hidden = np < 2;
    this.prev.disabled = this.page === 0;
    this.prev.style.visibility = np < 2 ? 'hidden' : '';

    this.dots.textContent = '';
    if (np > 1 && np <= 14) {
      for (var i = 0; i < np; i++) {
        (function (k) {
          var d = el('button', 'rd-dot' + (k === self.page ? ' on' : ''));
          d.type = 'button';
          d.setAttribute('aria-label', 'Page ' + (k + 1));
          if (k === self.page) d.setAttribute('aria-current', 'page');
          d.addEventListener('click', function () { self.go(k); });
          self.dots.appendChild(d);
        })(i);
      }
    }

    /* The last page's button finishes the topic: tick it, then move on. */
    this.next.textContent = '';
    this.next.classList.remove('rd-done');
    var t = this.tick(), nl = this.nextLink();
    if (!last) {
      this.next.appendChild(el('span', '', 'Next page')); this.next.appendChild(icon(I.right));
      this.next.hidden = false;
    } else if (t && !t.checked) {
      this.next.appendChild(icon(I.check)); this.next.appendChild(el('span', '', 'Finished — mark this topic done'));
      this.next.classList.add('rd-done');
      this.next.hidden = false;
    } else if (nl) {
      this.next.appendChild(el('span', '', (nl.textContent || '').indexOf('module') > -1 ? 'Next module' : 'Next topic'));
      this.next.appendChild(icon(I.right));
      this.next.hidden = false;
    } else {
      this.next.hidden = true;
    }

    this.resume.textContent = this.page > 0 ? 'Continue from page ' + (this.page + 1) : '';
    this.paintListen();
  };

  Reader.prototype.paintListen = function () {
    if (!this.listen) return;
    var on = Speaker.reader === this && Speaker.playing;
    this.listen.textContent = '';
    this.listen.appendChild(icon(on ? I.pause : I.play));
    this.listen.appendChild(el('span', '', on ? 'Pause' : 'Listen'));
    this.listen.classList.toggle('on', on);
    this.listen.setAttribute('aria-pressed', on ? 'true' : 'false');
  };

  /* ---- Read aloud ---------------------------------------------------------- */

  var Speaker = {
    ok: !!(window.speechSynthesis && window.SpeechSynthesisUtterance),
    reader: null, playing: false, mode: 'topic',
    items: [], at: 0, chunks: [], ci: 0, run: 0,
    rate: +(store.get('read:rate') || 1) || 1,
    voice: null,

    pickVoice: function () {
      var vs = window.speechSynthesis.getVoices() || [];
      var en = vs.filter(function (v) { return /^en/i.test(v.lang); });
      if (!en.length) return null;
      var rank = function (v) {
        var s = 0;
        if (/en[-_]ZA/i.test(v.lang)) s += 40; else if (/en[-_]GB/i.test(v.lang)) s += 30;
        else if (/en[-_](AU|IE|NZ|IN)/i.test(v.lang)) s += 20; else if (/en[-_]US/i.test(v.lang)) s += 15;
        if (/natural|online|neural|google/i.test(v.name)) s += 25;
        if (v.localService === false) s += 3;
        return s;
      };
      en.sort(function (a, b) { return rank(b) - rank(a); });
      return en[0];
    },

    toggle: function (reader, mode) {
      if (this.reader === reader && this.playing) { this.pause(); return; }
      if (this.reader === reader && !this.playing && this.items.length) { this.mode = mode || this.mode; this.resume(); return; }
      this.start(reader, mode);
    },

    start: function (reader, mode) {
      this.stop(true);
      this.reader = reader;
      this.mode = mode || 'topic';
      if (reader.box.hidden) reader.toggle(true);
      this.load();
      this.playing = true;
      this.player();
      this.speak();
    },

    /* What there is to say on the reader's current page, block by block. */
    load: function () {
      var r = this.reader;
      this.items = Array.prototype.slice.call(r.sheet.querySelectorAll('.rd-h, p, li'));
      this.at = 0; this.ci = 0;
      this.chunks = this.items.length ? this.split(this.items[0]) : [];
    },

    /* Sentences, joined up to ~220 characters. Long single utterances stall
       in some browsers, and short ones let pause / resume land near the spot. */
    split: function (node) {
      var t = (node.textContent || '').replace(/\s+/g, ' ').trim();
      var s = t.match(/[^.!?]+[.!?]+["')\]]*\s*|[^.!?]+$/g) || [t];
      var out = [], cur = '';
      s.forEach(function (x) {
        if ((cur + x).length > 220 && cur) { out.push(cur.trim()); cur = ''; }
        cur += x;
      });
      if (cur.trim()) out.push(cur.trim());
      return out;
    },

    speak: function () {
      var self = this;
      if (!this.playing) return;
      if (this.at >= this.items.length) { this.turn(); return; }
      var node = this.items[this.at];
      if (this.ci >= this.chunks.length) {
        this.at++; this.ci = 0;
        this.chunks = this.at < this.items.length ? this.split(this.items[this.at]) : [];
        this.speak();
        return;
      }
      this.mark(node);
      var u = new SpeechSynthesisUtterance(this.chunks[this.ci]);
      if (!this.voice) this.voice = this.pickVoice();
      if (this.voice) { u.voice = this.voice; u.lang = this.voice.lang; } else { u.lang = 'en-ZA'; }
      u.rate = this.rate;
      var run = ++this.run;
      /* setTimeout: never let one utterance's end call the next on the same stack. */
      u.onend = function () { if (run !== self.run) return; self.ci++; setTimeout(function () { self.speak(); }, 0); };
      /* Interrupted / canceled is us pausing. Anything else means the device
         cannot speak (no voice installed, engine missing): stop and say so,
         rather than skipping silently through every page. */
      u.onerror = function (e) {
        if (run !== self.run || e.error === 'interrupted' || e.error === 'canceled') return;
        self.fail();
      };
      window.speechSynthesis.speak(u);
    },

    /* End of the page: turn it; end of the topic: stop, or carry on with the
       next topic when listening to the whole module. */
    turn: function () {
      var r = this.reader;
      if (r.page < r.pages.length - 1) {
        r.go(r.page + 1, true);
        this.load(); this.player(); this.speak();
        return;
      }
      var i = readers.indexOf(r);
      if (this.mode === 'module' && i > -1 && i < readers.length - 1) {
        var nx = readers[i + 1];
        this.clear();
        r.paintListen();
        this.reader = nx;
        if (nx.box.hidden) nx.toggle(true);
        nx.go(0, true);
        this.load(); this.player(); this.speak();
        return;
      }
      this.stop();
    },

    mark: function (node) {
      this.clear();
      node.classList.add('rd-say');
      var r = node.getBoundingClientRect();
      if (r.top < 140 || r.bottom > window.innerHeight - 120) node.scrollIntoView({ block: 'center' });
    },
    clear: function () {
      document.querySelectorAll('.rd-say').forEach(function (n) { n.classList.remove('rd-say'); });
    },

    pause: function () {
      this.playing = false;
      this.run++;
      window.speechSynthesis.cancel();
      this.player();
    },
    resume: function () {
      if (!this.reader) return;
      this.playing = true;
      this.player();
      this.speak();
    },
    /* The learner turned the page by hand while listening: read from the top of it. */
    restart: function () {
      this.run++;
      window.speechSynthesis.cancel();
      this.load();
      this.player();
      this.speak();
    },
    stop: function (quiet) {
      this.run++;
      if (this.ok) window.speechSynthesis.cancel();
      this.playing = false;
      this.clear();
      var r = this.reader;
      this.reader = null; this.items = [];
      if (r) r.paintListen();
      if (!quiet) this.player();
    },
    fail: function () {
      var r = this.reader;
      this.stop();
      if (!r) return;
      var bar = r.box.querySelector('.rd-bar'), n = bar.querySelector('.rd-note');
      if (!n) { n = el('span', 'rd-note'); n.setAttribute('role', 'status'); bar.appendChild(n); }
      n.textContent = 'This browser could not read aloud. Try Chrome or Edge, or check the device has a voice installed.';
    },
    setRate: function (v) {
      this.rate = v;
      store.set('read:rate', String(v));
      if (this.playing) { this.run++; window.speechSynthesis.cancel(); this.speak(); }
    },

    /* The bar along the bottom while something is being read. */
    player: function () {
      var self = this, p = document.getElementById('rdPlayer');
      if (!this.reader) { if (p) p.remove(); paintModule(); return; }
      if (!p) {
        p = el('div', 'rd-player');
        p.id = 'rdPlayer';
        p.setAttribute('role', 'region');
        p.setAttribute('aria-label', 'Read aloud');
        p.innerHTML = '';
        var pp = el('button', 'rd-pp'); pp.type = 'button';
        pp.addEventListener('click', function () { self.playing ? self.pause() : self.resume(); });
        var info = el('a', 'rd-pinfo');
        info.href = '#';
        info.addEventListener('click', function (e) { e.preventDefault(); if (self.reader) self.reader.box.scrollIntoView({ block: 'start' }); });
        info.appendChild(el('strong')); info.appendChild(el('span'));
        var rate = el('select', 'rd-rate');
        rate.setAttribute('aria-label', 'Reading speed');
        [0.75, 1, 1.25, 1.5].forEach(function (v) {
          var o = el('option', '', v + '×'); o.value = String(v); rate.appendChild(o);
        });
        rate.addEventListener('change', function () { self.setRate(+rate.value); });
        var st = el('button', 'rd-stop'); st.type = 'button';
        st.setAttribute('aria-label', 'Stop reading aloud');
        st.appendChild(icon(I.stop));
        st.addEventListener('click', function () { self.stop(); });
        p.appendChild(pp); p.appendChild(info); p.appendChild(rate); p.appendChild(st);
        document.body.appendChild(p);
      }
      var r = this.reader;
      var b = p.querySelector('.rd-pp');
      b.textContent = '';
      b.appendChild(icon(this.playing ? I.pause : I.play));
      b.setAttribute('aria-label', this.playing ? 'Pause' : 'Carry on reading');
      p.querySelector('.rd-pinfo strong').textContent = r.title;
      p.querySelector('.rd-pinfo span').textContent =
        (this.mode === 'module' ? 'Topic ' + (readers.indexOf(r) + 1) + ' of ' + readers.length + ' · ' : '') +
        'Page ' + (r.page + 1) + ' of ' + r.pages.length + (this.playing ? '' : ' · paused');
      p.querySelector('.rd-rate').value = String(this.rate);
      readers.forEach(function (x) { x.paintListen(); });
      paintModule();
    }
  };

  /* ---- "Listen to this module" --------------------------------------------- */

  var modBtn = null;
  function moduleBar() {
    var host = document.getElementById('m-topics');
    if (!host) return;
    var total = readers.reduce(function (a, r) { return a + r.total; }, 0);
    var bar = el('div', 'rd-module');
    var ic = el('span', 'rd-module-ico'); ic.appendChild(icon(I.ear));
    var tx = el('div', 'rd-module-txt');
    tx.appendChild(el('strong', '', 'Listen to this module'));
    tx.appendChild(el('span', '', (readers.length === 1 ? 'The topic' : 'All ' + readers.length + ' topics') +
      ' read aloud, page by page · ' + readingTime(total, 150) + ' at normal speed'));
    modBtn = el('button', 'rd-btn rd-listen rd-module-go');
    modBtn.type = 'button';
    modBtn.addEventListener('click', function () {
      if (Speaker.mode === 'module' && Speaker.reader) { Speaker.playing ? Speaker.pause() : Speaker.resume(); return; }
      /* The whole module starts at the beginning, wherever the reading had got to. */
      readers[0].go(0, true);
      Speaker.start(readers[0], 'module');
    });
    bar.appendChild(ic); bar.appendChild(tx); bar.appendChild(modBtn);
    host.parentNode.insertBefore(bar, host);
    paintModule();
  }
  function paintModule() {
    if (!modBtn) return;
    var on = Speaker.mode === 'module' && Speaker.reader && Speaker.playing;
    var paused = Speaker.mode === 'module' && Speaker.reader && !Speaker.playing;
    modBtn.textContent = '';
    modBtn.appendChild(icon(on ? I.pause : I.play));
    modBtn.appendChild(el('span', '', on ? 'Pause' : (paused ? 'Carry on' : 'Listen')));
    modBtn.classList.toggle('on', !!on);
  }

  if (Speaker.ok) {
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
      window.speechSynthesis.onvoiceschanged = function () { Speaker.voice = Speaker.pickVoice(); };
    }
    /* Speech carries on across a page change in some browsers. Not here. */
    window.addEventListener('pagehide', function () { window.speechSynthesis.cancel(); });
  }
})();
