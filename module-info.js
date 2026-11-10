/* Module info on hover (10 Nov 2026).
 *
 * Kgomotso: "When you hover over [a file or module], it must give information
 * on the module." Any module link or row on the page gets a small card on
 * hover or keyboard focus: the module's title, what it is for, and the topics
 * it covers, read from the curriculum files the page already loads
 * (pm-modules.js, po-modules.js, nvc-modules.js).
 *
 * Works on:
 *   - [data-mod] rows (files.php), course from the closest [data-course]
 *   - any link to module?m=... (my.php, course.html), course from its &c=,
 *     else the page's ?c=, else the Project Manager (the links leave c off
 *     for that one)
 *
 * NO CREDITS ON A SHORT COURSE. Procurement Officer and New Venture Creation
 * are listed as non-accredited short courses (Kgomotso, 26 Oct 2026), and their
 * curriculum files still carry the old registered figures for the self-check.
 * A card that printed them would claim credits the course does not award, so
 * credits and hours show only where the curriculum is not marked shortCourse.
 */
(function () {
  'use strict';

  var CUR = window.ACADEMY_CURRICULA || {};
  if (!Object.keys(CUR).length) return;

  var pageCourse = new URLSearchParams(location.search).get('c') || '';
  var card = null, timer = null, current = null;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function target(el) {
    var t = el.closest && el.closest('[data-mod], a[href*="module?m="]');
    return t || null;
  }

  function lookup(el) {
    var id = el.getAttribute('data-mod'), course = '';
    var holder = el.closest('[data-course]');
    if (holder) course = holder.getAttribute('data-course');
    if (!id) {
      var href = el.getAttribute('href') || '';
      var q = new URLSearchParams(href.split('?')[1] || '');
      id = (q.get('m') || '').toUpperCase();
      course = q.get('c') || course;
    }
    course = course || pageCourse || 'project-management';
    var cur = CUR[course];
    if (!id) return null;
    var m = cur && cur.byId ? cur.byId[id] : null;
    if (!m && el.getAttribute('data-practical')) {
      return { cur: cur || {}, m: { id: id, title: el.getAttribute('data-title'), practical: true } };
    }
    return m ? { cur: cur, m: m } : null;
  }

  function html(info) {
    var m = info.m, cur = info.cur;
    var meta = [esc(m.id)];
    if (!cur.shortCourse) {
      if (m.credits) meta.push(esc(m.credits) + ' credits');
      if (m.hours)   meta.push(esc(m.hours) + ' hours');
    }
    var out = '<span class="mi-meta">' + meta.join(' · ') + '</span>' +
              '<strong class="mi-title">' + esc(m.title) + '</strong>';
    if (m.practical) {
      out += '<p class="mi-purpose">A practical module. Open the practical manual to see what to do, ' +
             'complete the practical workbook, and upload it under Submit work.</p>';
      return out;
    }
    if (m.purpose) out += '<p class="mi-purpose">' + esc(m.purpose) + '</p>';
    var topics = (m.topics || []).map(function (t) { return t.n || t.title || ''; }).filter(Boolean);
    if (topics.length) {
      var shown = topics.slice(0, 6);
      out += '<span class="mi-h">What it covers</span><ul class="mi-topics">' +
             shown.map(function (t) { return '<li>' + esc(t) + '</li>'; }).join('') + '</ul>';
      if (topics.length > shown.length) {
        out += '<span class="mi-more">and ' + (topics.length - shown.length) + ' more</span>';
      }
    }
    return out;
  }

  function place(el) {
    var r = el.getBoundingClientRect(), w = card.offsetWidth, h = card.offsetHeight;
    var left = Math.min(Math.max(12, r.left), window.innerWidth - w - 12);
    var top = r.bottom + 8;
    if (top + h > window.innerHeight - 12) top = Math.max(12, r.top - h - 8);
    card.style.left = left + 'px';
    card.style.top = top + 'px';
  }

  function show(el) {
    var info = lookup(el);
    if (!info) return;
    if (!card) {
      card = document.createElement('div');
      card.className = 'mod-info';
      card.setAttribute('role', 'tooltip');
      card.id = 'mod-info';
      document.body.appendChild(card);
    }
    card.innerHTML = html(info);
    card.classList.add('on');
    place(el);
    el.setAttribute('aria-describedby', 'mod-info');
    current = el;
  }

  function hide() {
    clearTimeout(timer);
    if (card) card.classList.remove('on');
    if (current) current.removeAttribute('aria-describedby');
    current = null;
  }

  document.addEventListener('mouseover', function (e) {
    var t = target(e.target);
    if (!t || t === current) return;
    clearTimeout(timer);
    timer = setTimeout(function () { show(t); }, 220);
  });
  document.addEventListener('mouseout', function (e) {
    var t = target(e.target);
    if (!t) return;
    if (e.relatedTarget && t.contains(e.relatedTarget)) return;
    hide();
  });
  document.addEventListener('focusin', function (e) { var t = target(e.target); if (t) show(t); });
  document.addEventListener('focusout', hide);
  window.addEventListener('scroll', hide, { passive: true });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });
})();
