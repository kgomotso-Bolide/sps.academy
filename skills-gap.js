/* ---- Skills Gap Analysis ----
   Runs entirely in the browser. Nothing is uploaded, stored or transmitted:
   answers live in this closure and disappear when the tab closes. That is a
   deliberate constraint — the form asks for qualifications and employment
   history, which is POPIA-regulated personal data, and this site is on public
   static hosting with nowhere safe to put it. */
(function(){
  var root=typeof document!=='undefined'?document.getElementById('sg'):null;

  /* ---------- Model ---------- */
  var LEVELS=["Not yet","Aware of it","Working level","Confident","Could teach it"];

  /* n = display name (headings, chart, table); l = the form used mid-sentence,
     hand-written rather than lowercased so "AI" and "POPIA" survive. */
  var SKILLS=[
    {k:'ai',    n:'Everyday AI tools',            l:'everyday AI tools',
     d:'Using an assistant to draft, summarise and sense-check your work'},
    {k:'data',  n:'Data &amp; spreadsheets',          l:'data and spreadsheets',
     d:'Yields, exports, formulas — getting numbers into a usable shape'},
    {k:'tech',  n:'Solar &amp; technical systems',    l:'solar and technical systems',
     d:'PV arrays, inverters, installs, fault-finding, monitoring platforms'},
    {k:'comms', n:'Customer &amp; written comms',     l:'customer and written communication',
     d:'Explaining a quote, a fault or an outcome so it lands first time'},
    {k:'report',n:'Reporting &amp; analysis',         l:'reporting and analysis',
     d:'Turning numbers into something a meeting can actually act on'},
    {k:'admin', n:'Digital admin &amp; workflow',     l:'digital admin and workflow',
     d:'Job cards, systems, tracking your own work without chasing paper'},
    {k:'resp',  n:'Responsible AI &amp; data privacy',l:'responsible AI and data privacy',
     d:'POPIA, customer data, and knowing when not to trust an AI answer'},
    {k:'lead',  n:'Leading &amp; developing people',  l:'leading and developing people',
     d:'Coaching, delegating, and running a team day to day'}
  ];

  /* Target proficiency per role. These are a first pass written from the site's
     own description of the work — they should be reviewed by someone who
     actually runs these teams before anyone treats the output as authoritative. */
  var ROLES=[
    {k:'install', n:'Solar Installer / PV Technician',t:{ai:3,data:3,tech:4,comms:2,report:2,admin:3,resp:3,lead:1}},
    {k:'field',   n:'Field Operations &amp; Maintenance',t:{ai:3,data:2,tech:3,comms:3,report:2,admin:3,resp:3,lead:2}},
    {k:'sales',   n:'Sales &amp; Projects',              t:{ai:3,data:3,tech:3,comms:4,report:3,admin:3,resp:3,lead:2}},
    {k:'care',    n:'Customer Care',                 t:{ai:4,data:2,tech:2,comms:4,report:2,admin:3,resp:4,lead:1}},
    {k:'datasys', n:'Data &amp; Systems',                t:{ai:4,data:4,tech:4,comms:2,report:4,admin:3,resp:4,lead:2}},
    {k:'lead',    n:'Team Lead / Supervisor',        t:{ai:3,data:3,tech:3,comms:3,report:3,admin:3,resp:3,lead:4}},
    {k:'manager', n:'Manager / Head of Department',  t:{ai:3,data:3,tech:2,comms:4,report:4,admin:3,resp:4,lead:4}},
    {k:'support', n:'Admin &amp; Support',               t:{ai:4,data:3,tech:1,comms:3,report:2,admin:4,resp:3,lead:1}}
  ];

  /* l = the form used mid-sentence. Hand-written for the same reason SKILLS
     needs one: lowercasing "Matric / NQF 4" produces "matric / nqf 4". */
  var QUALS=[
    {k:'none',   n:'No formal qualification yet', nqf:0, l:'nothing formal'},
    {k:'matric', n:'Matric / NQF 4',              nqf:4, l:'Matric'},
    {k:'cert',   n:'Certificate / NQF 5',         nqf:5, l:'a Certificate at NQF 5'},
    {k:'diploma',n:'National Diploma / NQF 6',    nqf:6, l:'a National Diploma'},
    {k:'degree', n:'Degree / NQF 7',              nqf:7, l:'a degree'},
    {k:'post',   n:'Postgraduate / NQF 8+',       nqf:8, l:'a postgraduate qualification'}
  ];

  var YEARS=[
    {k:'lt1',n:'Less than a year', v:0.5},
    {k:'1-2',n:'1–2 years',        v:2},
    {k:'3-5',n:'3–5 years',        v:4},
    {k:'6-10',n:'6–10 years',      v:8},
    {k:'10+',n:'More than 10 years',v:12}
  ];

  var BACKGROUNDS=[
    {k:'energy',n:'Solar / energy'},
    {k:'technical',n:'Technical or a trade'},
    {k:'service',  n:'Retail or customer service'},
    {k:'office',   n:'Office / admin'},
    {k:'finance',  n:'Finance'},
    {k:'it',       n:'IT'},
    {k:'first',    n:'This is my first job'},
    {k:'other',    n:'Something else'}
  ];

  /* Course catalogue with the skills each one moves. Slugs match course.html.
     The three AI-in-Medicine courses are deliberately absent — they map to
     nothing in a solar business, so recommending them would be noise. */
  var COURSES=[
    {s:'ai-fundamentals',         n:'AI Fundamentals for the Workplace',            c:'Business',           w:{ai:3,resp:1,comms:1}},
    {s:'ai-tools-productivity',   n:'AI Tools for Productivity',                    c:'Business',           w:{ai:3,admin:3,comms:2,data:1}},
    {s:'responsible-ai',          n:'Responsible &amp; Ethical AI Use',                 c:'Compliance',         w:{resp:4,ai:1}},
    {s:'ai-essentials-business',  n:'AI Essentials for Business',                   c:'Business',           w:{ai:2,data:2,report:2,comms:1}},
    {s:'ai-leaders',              n:'AI for Leaders &amp; Managers',                    c:'Leadership',         w:{lead:3,ai:2,report:1}},
    {s:'ai-ethics-business',      n:'AI Ethics in Business',                        c:'Business',           w:{resp:4,lead:1}},
    {s:'ai-strategy',             n:'AI Strategy for Business Leaders',             c:'Business',           w:{lead:3,report:2}},
    {s:'ai-in-action',            n:'AI in Action: Organisational Strategy',        c:'Social Sciences',    w:{lead:2,resp:2,report:1}},
    {s:'competing-age-ai',        n:'Competing in the Age of AI',                   c:'Business',           w:{lead:3,report:1}},
    {s:'leadership-emerging-tech',n:'Leadership in Emerging Technology',            c:'Social Sciences',    w:{lead:3,resp:2,tech:1}},
    {s:'cybersecurity',           n:'Cybersecurity: Policy &amp; Technology',           c:'Social Sciences',    w:{resp:3,tech:2}},
    {s:'computer-technician',     n:'Occupational Certificate: Computer Technician',c:'Technical',           w:{tech:4,admin:1},
     formal:true, formalFor:['install','field','datasys'], formalNoun:'technical'},
    {s:'project-management',      n:'Occupational Certificate: Project Manager', c:'Project Management',  w:{lead:3,admin:3,report:2,comms:1},
     formal:true, formalFor:['sales','lead','manager'],     formalNoun:'project-management'},
    {s:'fundamentals-tinyml',     n:'Fundamentals of TinyML',                       c:'Computer Science',   w:{tech:3,data:2}},
    {s:'deploying-tinyml',        n:'Deploying TinyML',                             c:'Computer Science',   w:{tech:4,data:2}},
    {s:'agentic-ai',              n:'Agentic AI Foundations',                       c:'Computer Science',   w:{ai:2,tech:2,resp:1}},
    {s:'new-venture',             n:'AI &amp; New Venture Creation',                    c:'Computer Science',   w:{lead:2,report:1}},
    {s:'cs-for-lawyers',          n:'Computer Science for Lawyers',                 c:'Social Sciences',    w:{resp:2,tech:1}}
  ];

  var answers={role:'',qual:'',years:'',bg:'',self:{}};
  SKILLS.forEach(function(s){answers.self[s.k]=null;});

  /* If a profile exists, step 1 is already answered — carry it across rather
     than asking the same four questions twice. Ratings are never prefilled:
     they're a point-in-time self-assessment, and seeding last year's answers
     would bias this year's. */
  var PROF=(typeof window!=='undefined'&&window.SPSProfile)||null;
  var prefilled=false;
  if(PROF&&PROF.exists()){
    var pp=PROF.get();
    ['role','qual','years','bg'].forEach(function(k){ if(pp[k]) answers[k]=pp[k]; });
    prefilled=!!(answers.role&&answers.qual&&answers.years&&answers.bg);
  }

  function byKey(list,k){for(var i=0;i<list.length;i++){if(list[i].k===k)return list[i];}return null;}
  function strip(h){return String(h).replace(/&amp;/g,'&');}

  /* ---------- Scoring ---------- */
  function analyse(a){
    a=a||answers;
    var role=byKey(ROLES,a.role), qual=byKey(QUALS,a.qual),
        yrs=byKey(YEARS,a.years);

    var rows=SKILLS.map(function(s){
      var self=a.self[s.k], target=role.t[s.k];
      return {k:s.k,n:s.n,l:s.l,d:s.d,self:self,target:target,gap:Math.max(0,target-self)};
    });

    var scored=COURSES.map(function(c){
      var score=0,covers=[];
      for(var k in c.w){
        var row=null;
        for(var i=0;i<rows.length;i++){if(rows[i].k===k)row=rows[i];}
        if(row&&row.gap>0){score+=c.w[k]*row.gap; if(row.gap>=1)covers.push(row);}
      }
      covers.sort(function(a,b){return b.gap-a.gap;});
      return {c:c,score:score,covers:covers,why:''};
    }).filter(function(x){return x.score>0;});

    /* Experience and qualification don't change what you're missing — they
       change which route to closing it makes sense. Someone with years of doing
       the job and no certificate needs the credential, not another short course.

       Each formal qualification names the roles it is the right route for. Without
       that, adding a second one meant the project-management certificate was
       pitched to installers as "the full technical route". */
    var underQualified=qual.nqf<=4;
    var experienced=yrs.v>=4;

    scored.forEach(function(x){
      if(x.c.formal){
        var fits=(x.c.formalFor||[]).indexOf(a.role)>=0;
        if(fits&&underQualified){
          x.score*=1.8;
          x.why=experienced
            ? 'You have the years of doing it but nothing on paper to show for them. This is the qualification that closes that.'
            : 'The full '+x.c.formalNoun+' route — it builds the base the rest of your role sits on, and it is yours to keep.';
        } else if(fits){
          x.score*=1.15;
          x.why='Puts the '+x.c.formalNoun+' side of your role on a formal footing rather than adding another short course.';
        }
      }
      if(a.bg==='first'&&x.c.s==='ai-fundamentals'){
        x.score*=1.5;
        x.why='A solid place to start — it assumes no background and everything else builds on it.';
      }
      if(!x.why&&x.covers.length){
        /* ", plus " not " and " — several skill names contain "and" themselves,
           and chaining them produced "data and spreadsheets and reporting and
           analysis". */
        x.why='Closes your gap in '+x.covers[0].l+
              (x.covers[1]?', plus '+x.covers[1].l:'')+'.';
      }
    });

    scored.sort(function(a,b){return b.score-a.score;});

    var sorted=rows.slice().sort(function(a,b){return b.gap-a.gap;});
    var gaps=sorted.filter(function(r){return r.gap>0;});
    var strengths=rows.filter(function(r){return r.self>=r.target&&r.self>=3;})
                      .sort(function(a,b){return b.self-a.self;});

    return {role:role,rows:rows,gaps:gaps,strengths:strengths,recs:scored.slice(0,4),
            qual:qual,yrs:yrs,rpl:rplCase(rows,qual,yrs)};
  }

  /* ---------- Recognition of Prior Learning ----------
     RPL is for people whose demonstrated competence has run ahead of their
     paper qualification — which is exactly the shape this tool already
     measures. Rather than ask four more questions, read it off the answers
     we have: what they rate themselves at, how long they've done the work,
     and what they hold on paper.

     Deliberately cautious. A false "you have a strong case" sends someone
     into an assessment they'll fail, so the strong tier needs real evidence
     behind it: years AND breadth AND depth. The QCTO recognises three
     purposes (access, credit, admission to the final assessment) and we name
     the ones that actually apply rather than listing all three every time. */
  function rplCase(rows,qual,yrs){
    if(qual.nqf>4) return null;               // already past the level RPL routes into

    var confident=rows.filter(function(r){return r.self>=3;});      // "Confident" or better
    var expert   =rows.filter(function(r){return r.self>=4;});      // "Could teach it"
    var atTarget =rows.filter(function(r){return r.self>=r.target;});
    var noPaper  =qual.nqf===0;

    var years=yrs.v;
    var strong = years>=8 && confident.length>=3 && atTarget.length>=4;
    var likely = years>=4 && confident.length>=2;

    /* Someone with no formal qualification at all has an access case on the
       strength of the qualification's entry requirements alone — that route
       does not depend on how they rated themselves. */
    if(!strong && !likely && !noPaper) return null;

    var purposes=[];
    /* Access only applies when there is genuinely nothing on paper. Matric is
       normally the entry requirement for an NQF 5 qualification, so telling a
       matric holder they need RPL to get in would be plainly wrong. */
    if(noPaper) purposes.push({
      t:'Access',
      d:'Getting you admitted to a qualification whose formal entry requirements you don\'t currently meet.'
    });
    if(likely||strong) purposes.push({
      t:'Credit',
      d:'Formal credits for what you can already prove, so you only study what is genuinely new to you.'
    });
    if(strong) purposes.push({
      t:'Straight to the final assessment',
      d:'Admission directly to the external exam that awards the certificate, without repeating the coursework.'
    });

    /* Evidence suggestions drawn from what they said they are good at, so the
       list is theirs rather than generic. */
    var ev=[];
    var byK={}; rows.forEach(function(r){byK[r.k]=r;});
    if(byK.tech&&byK.tech.self>=3) ev.push('Job cards, fault reports and hand-over sheets from installs and repairs you signed off');
    if(byK.data&&byK.data.self>=3) ev.push('Yields, exports or spreadsheets you built that other people rely on');
    if(byK.report&&byK.report.self>=3) ev.push('Reports you produced, and what was decided off the back of them');
    if(byK.comms&&byK.comms.self>=3) ev.push('Customer correspondence or complaint resolutions you handled end to end');
    if(byK.admin&&byK.admin.self>=3) ev.push('Records showing you running your own workload — job tracking, systems, sign-offs');
    if(byK.lead&&byK.lead.self>=3) ev.push('Who you have trained or supervised, and for how long');
    ev.push('A letter from your supervisor confirming what you have been trusted to do unsupervised');
    ev.push('Certificates from any in-house or supplier training, even if it was never accredited');

    var line;
    var onPaper=noPaper?'you have <b>nothing formal</b> on paper'
                       :'on paper you hold <b>'+qual.l+'</b>';
    if(strong){
      line='You have <b>'+yrs.n.toLowerCase()+'</b> in this kind of work, you are at or above target on '+
           atTarget.length+' of '+rows.length+' areas'+
           (expert.length?', and you rate yourself able to teach '+expert.length+' of them':'')+
           ' — and '+onPaper+'. '+
           'That distance between what you can do and what you hold on paper is the whole reason RPL exists.';
    } else if(likely){
      line='You have <b>'+yrs.n.toLowerCase()+'</b> behind you and rate yourself confident in '+
           confident.length+' area'+(confident.length>1?'s':'')+', and '+onPaper+
           '. That is worth a conversation — some of what you already do may count for credits.';
    } else {
      line='You don\'t hold a formal qualification yet, and that normally blocks entry to an accredited one. '+
           'RPL for access exists specifically to get round that: you are admitted on what you can demonstrate '+
           'rather than on a school certificate.';
    }

    return {tier:strong?'strong':(likely?'likely':'access'),line:line,purposes:purposes,evidence:ev};
  }


  /* The RPL block only appears when the answers actually support it. Showing
     it to everyone would turn it into wallpaper and send people who have no
     case into an assessment they aren't ready for. */
  function renderRpl(a){
    var r=a.rpl; if(!r) return '';
    var head=r.tier==='strong' ? 'You look like a strong RPL candidate'
           : r.tier==='likely' ? 'RPL is worth asking about'
                               : 'RPL for access may be your route in';
    return '<div class="sg-card sg-rpl">'+
      '<span class="rpl-flag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" '+
        'stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5l-8-3Z"/>'+
        '<path d="m9 12 2 2 4-4"/></svg>Recognition of Prior Learning</span>'+
      '<h3>'+head+'</h3>'+
      '<p class="sg-lede">'+r.line+'</p>'+
      '<h4>What it could do for you</h4>'+
      '<ul>'+r.purposes.map(function(p){
        return '<li><b>'+p.t+'</b> — '+p.d+'</li>';
      }).join('')+'</ul>'+
      '<h4>Evidence you probably already have</h4>'+
      '<ul>'+r.evidence.map(function(e){return '<li>'+e+'</li>';}).join('')+'</ul>'+
      '<div class="sg-actions">'+
        '<a class="btn btn-primary" href="rpl">How RPL works</a>'+
        '<a class="btn btn-ghost-dark" href="contact?course='+
          encodeURIComponent('Recognition of Prior Learning — enquiry')+'">Ask HR about RPL</a>'+
      '</div>'+
      '<p class="sg-priv">This is a prompt to go and ask, not an assessment. Whether you are awarded '+
      'anything depends on evidence assessed against the qualification standard, and on the accreditation '+
      'scope held for it. Print this page and take it with you — the evidence list above is a decent '+
      'starting point for the conversation.</p>'+
    '</div>';
  }

  /* ---------- Rendering ---------- */
  function sel(id,list,label,hint){
    return '<div class="field"><label for="'+id+'">'+label+'</label>'+
      '<select id="'+id+'"><option value="">Choose…</option>'+
      list.map(function(o){return '<option value="'+o.k+'">'+o.n+'</option>';}).join('')+
      '</select>'+(hint?'<span class="sg-hint">'+hint+'</span>':'')+'</div>';
  }

  function renderStep1(){
    return '<div class="sg-card">'+
      '<div class="sg-stepnum">Step 1 of 3</div>'+
      '<h3>A bit about you</h3>'+
      '<p class="sg-intro">This shapes what we compare you against — a target for a solar installer is not the target for someone in sales.</p>'+
      (prefilled?'<p class="prefill-note">Filled in from <a href="profile">your profile</a>. Change anything that\'s moved on since.</p>':'')+
      '<div class="two">'+
        sel('sgRole',ROLES,'Your role at SPS')+
        sel('sgYears',YEARS,'Years doing this kind of work')+
      '</div>'+
      '<div class="two">'+
        sel('sgQual',QUALS,'Highest qualification')+
        sel('sgBg',BACKGROUNDS,'What you did before')+
      '</div>'+
      '<div class="sg-nav"><button class="btn btn-primary" id="sgNext1" disabled>Continue</button></div>'+
    '</div>';
  }

  function renderStep2(){
    return '<div class="sg-card">'+
      '<div class="sg-stepnum">Step 2 of 3</div>'+
      '<h3>Rate yourself, honestly</h3>'+
      '<p class="sg-intro">No one else sees this. Over-rating yourself only produces a worse recommendation — the tool is looking for the gaps, so give it real ones.</p>'+
      '<div class="sg-rate">'+SKILLS.map(function(s,i){
        return '<div class="sg-skill" data-k="'+s.k+'">'+
          '<div class="sg-skill-h"><strong>'+s.n+'</strong><span>'+s.d+'</span></div>'+
          '<div class="sg-scale" role="radiogroup" aria-label="'+strip(s.n)+'">'+
            LEVELS.map(function(l,li){
              return '<button type="button" class="sg-lv" data-k="'+s.k+'" data-v="'+li+'" '+
                     'role="radio" aria-checked="false" title="'+l+'"><b>'+li+'</b><span>'+l+'</span></button>';
            }).join('')+
          '</div>'+
          /* The per-button words are hidden on narrow screens, so the scale
             needs restating somewhere a phone user can still read it. */
          '<div class="sg-scalekey">0 = '+LEVELS[0].toLowerCase()+' · 4 = '+LEVELS[4].toLowerCase()+'</div>'+
          '</div>';
      }).join('')+'</div>'+
      '<div class="sg-nav"><button class="btn btn-ghost-dark" id="sgBack2">Back</button>'+
      '<button class="btn btn-primary" id="sgNext2" disabled>See my results</button></div>'+
    '</div>';
  }

  function pct(v){return (v/4*100);}

  function statusOf(gap){
    if(gap<=0) return {t:'On track',c:'ok'};
    if(gap===1) return {t:'Small gap',c:'mid'};
    return {t:'Priority',c:'hi'};
  }

  function renderChart(a){
    return '<div class="gapchart">'+
      '<div class="gapkey">'+
        '<span><i class="k-bar"></i>Where you are now</span>'+
        '<span><i class="k-mark"></i>Target for a '+strip(a.role.n)+'</span>'+
      '</div>'+
      a.rows.map(function(r){
        var st=statusOf(r.gap);
        var tip=strip(r.n)+' — you: '+LEVELS[r.self]+' · target: '+LEVELS[r.target]+
                (r.gap?' · '+r.gap+' level'+(r.gap>1?'s':'')+' to close':' · at target');
        return '<div class="gaprow" title="'+tip+'">'+
          '<div class="gaplabel">'+r.n+'</div>'+
          '<div class="gaptrack">'+
            '<div class="gapbar" style="width:'+pct(r.self)+'%"></div>'+
            '<div class="gapmark" style="left:'+pct(r.target)+'%"></div>'+
          '</div>'+
          '<div class="gapstat '+st.c+'">'+st.t+'</div>'+
        '</div>';
      }).join('')+
      '<div class="gapaxis"><span>Not yet</span><span>Working</span><span>Could teach it</span></div>'+
    '</div>';
  }

  function renderTable(a){
    return '<table class="sg-table"><caption>Your ratings against the target for a '+strip(a.role.n)+'</caption>'+
      '<thead><tr><th scope="col">Skill area</th><th scope="col">You</th><th scope="col">Target</th><th scope="col">Gap</th></tr></thead><tbody>'+
      a.rows.map(function(r){
        return '<tr><th scope="row">'+r.n+'</th><td>'+r.self+' — '+LEVELS[r.self]+'</td>'+
               '<td>'+r.target+' — '+LEVELS[r.target]+'</td><td>'+(r.gap||'—')+'</td></tr>';
      }).join('')+'</tbody></table>';
  }

  function renderSummary(a){
    var s;
    if(!a.gaps.length){
      s='<p class="sg-lede">You are at or above target on every area we measure for a '+strip(a.role.n)+'. '+
        'That makes you a candidate for stretching sideways — or for helping teach the people coming up behind you.</p>';
    } else {
      var top=a.gaps.slice(0,2).map(function(r){return '<b>'+r.l+'</b>';});
      s='<p class="sg-lede">Your biggest gaps are '+top.join(' and ')+'. '+
        (a.strengths.length? 'You are already strong on <b>'+a.strengths[0].l+'</b>, so that is ground you can build from.'
                           : 'Everything below is ordered so the highest-impact course comes first.')+'</p>';
    }
    return s;
  }

  function renderResults(){
    var a=analyse();
    var html='<div class="sg-card sg-result">'+
      '<div class="sg-stepnum">Step 3 of 3 · Your result</div>'+
      '<h3>Where you are against a '+strip(a.role.n)+'</h3>'+
      renderSummary(a)+
      renderChart(a)+
      '<div class="sg-tablewrap"><button class="sg-toggle" id="sgTableBtn" aria-expanded="false">Show the numbers</button>'+
      '<div class="sg-tablebox" id="sgTableBox" hidden>'+renderTable(a)+'</div></div>'+
    '</div>';

    html+='<div class="sg-card">'+
      '<h3>What we suggest you take</h3>'+
      '<p class="sg-intro">Ranked by how much of your gap each course actually closes — not by what is popular.</p>'+
      '<div class="sg-recs">'+a.recs.map(function(x,i){
        return '<a class="sg-rec'+(x.c.formal?' accred':'')+'" href="course?c='+x.c.s+'">'+
          '<div class="sg-rank">'+(i+1)+'</div>'+
          '<div class="sg-recbody">'+
            '<div class="sg-reccat">'+x.c.c+'</div>'+
            '<h4>'+x.c.n+'</h4>'+
            '<p>'+x.why+'</p>'+
          '</div>'+
          '<div class="sg-recgo" aria-hidden="true">→</div>'+
        '</a>';
      }).join('')+'</div>'+
      (a.recs.length?'':'<p class="sg-intro">Nothing is showing a meaningful gap — talk to your manager about a stretch goal instead.</p>')+
      '<div class="sg-actions">'+
        '<a class="btn btn-primary" href="contact?course='+encodeURIComponent(strip(a.recs.length?a.recs[0].c.n:''))+'">Register for '+(a.recs.length?'the first one':'a course')+'</a>'+
        (PROF?'<button class="btn btn-ghost-dark" id="sgSave">Save to my profile</button>':'')+
        '<button class="btn btn-ghost-dark" id="sgPrint">Print / save as PDF</button>'+
        '<button class="btn btn-ghost-dark" id="sgRestart">Start over</button>'+
      '</div>'+
      '<p class="sg-priv" id="sgPriv">Your answers were never uploaded. Unless you save them to your profile they disappear when you close the tab — and even saved, they only ever sit in this browser on this device. Print this page if you want to take it to your manager.</p>'+
    '</div>';

    html+=renderRpl(a);
    return html;
  }

  /* ---------- Wiring ---------- */
  function checkStep1(){
    var ok=answers.role&&answers.years&&answers.qual&&answers.bg;
    var b=document.getElementById('sgNext1'); if(b) b.disabled=!ok;
  }
  function checkStep2(){
    var done=SKILLS.every(function(s){return answers.self[s.k]!==null;});
    var b=document.getElementById('sgNext2'); if(b) b.disabled=!done;
  }

  function go(step){
    if(step===1) root.innerHTML=renderStep1();
    if(step===2) root.innerHTML=renderStep2();
    if(step===3) root.innerHTML=renderResults();
    bind(step);
    if(step>1){
      var y=root.getBoundingClientRect().top+window.pageYOffset-90;
      window.scrollTo({top:y,behavior:'smooth'});
    }
  }

  function bind(step){
    if(step===1){
      [['sgRole','role'],['sgYears','years'],['sgQual','qual'],['sgBg','bg']].forEach(function(p){
        var el=document.getElementById(p[0]);
        if(answers[p[1]]) el.value=answers[p[1]];
        el.addEventListener('change',function(){answers[p[1]]=el.value;checkStep1();});
      });
      checkStep1();
      document.getElementById('sgNext1').addEventListener('click',function(){go(2);});
    }
    if(step===2){
      root.querySelectorAll('.sg-lv').forEach(function(b){
        var k=b.dataset.k, v=+b.dataset.v;
        if(answers.self[k]===v){b.classList.add('on');b.setAttribute('aria-checked','true');}
        b.addEventListener('click',function(){
          answers.self[k]=v;
          root.querySelectorAll('.sg-lv[data-k="'+k+'"]').forEach(function(o){
            var on=+o.dataset.v===v;
            o.classList.toggle('on',on);
            o.setAttribute('aria-checked',on?'true':'false');
          });
          checkStep2();
        });
      });
      checkStep2();
      document.getElementById('sgBack2').addEventListener('click',function(){go(1);});
      document.getElementById('sgNext2').addEventListener('click',function(){go(3);});
    }
    if(step===3){
      var tb=document.getElementById('sgTableBtn'), bx=document.getElementById('sgTableBox');
      tb.addEventListener('click',function(){
        var open=bx.hasAttribute('hidden');
        if(open){bx.removeAttribute('hidden');tb.textContent='Hide the numbers';}
        else{bx.setAttribute('hidden','');tb.textContent='Show the numbers';}
        tb.setAttribute('aria-expanded',open?'true':'false');
      });
      var sv=document.getElementById('sgSave');
      if(sv) sv.addEventListener('click',function(){
        if(!PROF.available()){ sv.textContent='Storage is blocked in this browser'; sv.disabled=true; return; }
        var a2=analyse();
        var ok=PROF.save({
          role:answers.role,qual:answers.qual,years:answers.years,bg:answers.bg,
          skills:{
            date:new Date().toISOString(),
            role:strip(a2.role.n),
            rows:a2.rows.map(function(r){return {k:r.k,n:r.n,self:r.self,target:r.target,gap:r.gap};})
          }
        });
        sv.textContent=ok?'Saved to your profile':'Could not save';
        sv.disabled=true;
      });
      document.getElementById('sgPrint').addEventListener('click',function(){window.print();});
      document.getElementById('sgRestart').addEventListener('click',function(){
        answers={role:'',qual:'',years:'',bg:'',self:{}};
        SKILLS.forEach(function(s){answers.self[s.k]=null;});
        go(1);
      });
    }
  }

  /* Exposed for the offline scoring tests; harmless in the browser. */
  if(typeof module!=='undefined'&&module.exports){
    module.exports={SKILLS:SKILLS,ROLES:ROLES,COURSES:COURSES,QUALS:QUALS,YEARS:YEARS,
                    BACKGROUNDS:BACKGROUNDS,LEVELS:LEVELS,analyse:analyse};
  }

  if(root) go(1);
})();
