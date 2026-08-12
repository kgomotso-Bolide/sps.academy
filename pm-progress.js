/* ---- Project Manager NQF 5: progress tracking ----------------------------

   The client asked how a self-paced programme gets tracked so people
   finish on time and there is something to report on, "without placing a strain
   on HR or the provider". This is the answer that needs no backend.

   WHAT THIS IS
     A learner ticks off topics as they work through them. Progress is kept in
     this browser, alongside the profile, so clearing the profile clears this too
     — which is the behaviour you want on a shared site machine. When a module is
     finished the learner submits a dated record to HR, and can print a report
     for a manager to countersign.

   WHAT THIS IS NOT — and the page says so plainly
     Self-reported effort, not an assessment result. Being found competent in a
     module is Centenary's decision after assessment, and the qualification is
     awarded by the QCTO after the EISA. Nothing here changes or predicts that.

   WHY NO DATABASE
     Five learners, and the completion documents HR actually needs for reporting
     come from the provider, not from us — a database would produce dashboards
     while the compliance artefact still arrives by email. Storing employee
     learning records on our own infrastructure would also make someone a
     responsible party under POPIA for data the employer already holds lawfully in its
     HR systems. Revisit at ~20 learners or when this rolls to the other sites;
     the submitted records are structured so they migrate cleanly.

   Progress lives under `pmProgress` inside the existing profile object:
     { "KM-01": { topics: { "KM-01-KT01": "2026-08-12T09:00:00.000Z" },
                  done: "2026-08-20T…" } } */
(function () {
  var FIELD = 'pmProgress';

  /* Each academy exposes its profile under its own global — SPSProfile,
     FungiProfile, EquinixProfile — because the sites were branded separately.
     Hardcoding one name silently disabled progress on the other two: available()
     returned false and the ticks removed themselves, with no error to notice.
     So find whichever object on `window` actually implements the profile API. */
  var cached = null;
  function profile() {
    if (cached) return cached;
    for (var k in window) {
      if (!/Profile$/.test(k)) continue;
      var o;
      try { o = window[k]; } catch (e) { continue; }   // cross-origin frames throw
      if (o && typeof o.get === 'function' && typeof o.save === 'function' &&
          typeof o.available === 'function') { cached = o; return o; }
    }
    return null;
  }

  function store() {
    var p = profile();
    return (p && p.get()[FIELD]) || {};
  }
  function commit(next) {
    var p = profile();
    if (!p) return false;
    return p.save(makePatch(next));
  }
  function makePatch(next) { var p = {}; p[FIELD] = next; return p; }
  function clone(o) { return JSON.parse(JSON.stringify(o || {})); }
  function now() { return new Date().toISOString(); }

  function moduleEntry(all, id) {
    if (!all[id]) all[id] = { topics: {}, done: null };
    if (!all[id].topics) all[id].topics = {};
    return all[id];
  }

  var API = {
    available: function () {
      var p = profile();
      return !!(p && p.available());
    },
    all: function () { return store(); },

    topicDone: function (moduleId, topicCode) {
      var m = store()[moduleId];
      return !!(m && m.topics && m.topics[topicCode]);
    },

    toggleTopic: function (moduleId, topicCode) {
      var all = clone(store());
      var m = moduleEntry(all, moduleId);
      if (m.topics[topicCode]) delete m.topics[topicCode];
      else m.topics[topicCode] = now();
      commit(all);
      return !!m.topics[topicCode];
    },

    moduleDone: function (moduleId) {
      var m = store()[moduleId];
      return (m && m.done) || null;
    },

    setModuleDone: function (moduleId, on) {
      var all = clone(store());
      var m = moduleEntry(all, moduleId);
      m.done = on ? now() : null;
      commit(all);
      return m.done;
    },

    /* Per-module counts. `total` comes from the curriculum, not from what the
       learner has touched, so an untouched module still reports out of its real
       number of topics. */
    moduleStats: function (mod) {
      var saved = store()[mod.id] || { topics: {} };
      var total = mod.topics.length;
      var done = mod.topics.filter(function (t) { return saved.topics && saved.topics[t.code]; }).length;
      return {
        id: mod.id, done: done, total: total,
        pct: total ? Math.round(done / total * 100) : 0,
        complete: !!saved.done, completedAt: saved.done || null
      };
    },

    overall: function (modules) {
      var t = 0, d = 0, mods = 0, credits = 0;
      modules.forEach(function (m) {
        var s = API.moduleStats(m);
        t += s.total; d += s.done;
        if (s.complete) { mods++; credits += (+m.credits || 0); }
      });
      return {
        topicsDone: d, topicsTotal: t,
        pct: t ? Math.round(d / t * 100) : 0,
        modulesComplete: mods, modulesTotal: modules.length,
        creditsClaimed: credits,
        creditsTotal: modules.reduce(function (a, m) { return a + (+m.credits || 0); }, 0)
      };
    },

    /* A flat, dated record — the thing that actually gets sent or printed. It is
       deliberately plain text: it has to survive an email client, a printout and
       being pasted into a spreadsheet years from now. */
    report: function (modules) {
      var o = API.overall(modules);
      var lines = modules.map(function (m) {
        var s = API.moduleStats(m);
        return [
          s.complete ? '[x]' : (s.done ? '[~]' : '[ ]'),
          m.id,
          m.title,
          s.done + '/' + s.total + ' topics',
          m.credits + ' cr',
          s.complete ? 'marked complete ' + s.completedAt.slice(0, 10) : ''
        ].filter(Boolean).join('  ');
      });
      return {
        summary: o,
        lines: lines,
        text: lines.join('\n')
      };
    },

    clear: function () { commit({}); }
  };

  window.PM_PROGRESS = API;
})();
