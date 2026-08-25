/* ---- Course index: four schools, then search + credential filter ----
   The catalogue is entered one school at a time. Landing on /courses shows the
   four school cards and nothing else; picking one reveals just that school's
   courses. Thirty cards at once is what this replaced — people stopped reading
   after the first row and never reached the accredited qualification.

   Three views, and only one is ever on screen:
     landing  no school, nothing typed  — the four school cards
     school   a school is open          — that school's cards, in their bands
     search   something is typed        — matches across all four schools

   As before this filters the cards already in the page rather than rendering
   them, so with JS off the whole catalogue simply reads as one long list. And
   note there is more than one .course-grid (the cards are split under band
   headings) — scoping to a single grid silently hid a third of the catalogue. */
(function(){
  var box=document.getElementById('cx'); if(!box) return;

  var grids=[].slice.call(document.querySelectorAll('.course-grid'));
  if(!grids.length) return;

  /* Each grid's heading and its band note, so a band with nothing left in it
     disappears whole rather than leaving a floating label behind. */
  var groups=grids.map(function(g){
    var el=g.previousElementSibling, lbl=null, note=null;
    while(el&&!lbl){
      if(el.classList.contains('band-note')) note=el;
      if(el.classList.contains('course-band-label')) lbl=el;
      el=el.previousElementSibling;
    }
    return {grid:g,label:lbl,note:note,cards:[].slice.call(g.querySelectorAll('.ccard'))};
  });
  var cards=groups.reduce(function(a,g){return a.concat(g.cards);},[]);

  /* The four schools. Business is first because the one accredited
     qualification lives in it. Every card carries data-school, so adding a
     course to a school is a matter of tagging the card — nothing here changes. */
  var SCHOOLS={
    business:{
      name:'Business School',
      blurb:'Project management, procurement, leadership and strategy — including the accredited Occupational Certificate: Project Manager, and the AI courses written for the people running the work.'
    },
    technology:{
      name:'Technology School',
      blurb:'Software development, generative and agentic AI, cybersecurity, and the computing underneath all of it. None of it assumes you have written code before.'
    },
    engineering:{
      name:'Engineering School',
      blurb:'Artisan and trade pathways, electrical compliance, solar PV, welding and plumbing, delivered with our technical training partner — plus embedded machine learning on microcontrollers.'
    },
    wellness:{
      name:'Wellness & Health School',
      blurb:'AI in medicine: clinical foundations, biomedical signal interpretation, and the language models now being used on medical records.'
    }
  };

  var intro=document.getElementById('schoolIntro'),
      chooser=document.getElementById('schools'),
      head=document.getElementById('schoolHead'),
      headTitle=document.getElementById('schoolTitle'),
      headBlurb=document.getElementById('schoolBlurb'),
      headEyebrow=document.getElementById('schoolEyebrow'),
      back=document.getElementById('schoolBack'),
      badge=document.getElementById('badgeNote'),
      soon=document.querySelector('.soon-note'),
      q=document.getElementById('cxq'),
      accRow=document.getElementById('cxAcc'),
      count=document.getElementById('cxCount');

  var state={q:'',acc:'all',school:''};

  /* Counts on the school cards are read off the cards, so they cannot drift out
     of date the way a hand-written number would. */
  [].slice.call(chooser.querySelectorAll('[data-school-count]')).forEach(function(el){
    var s=el.getAttribute('data-school-count');
    var n=cards.filter(function(c){return c.dataset.school===s;}).length;
    el.textContent=n+(n===1?' course':' courses');
  });

  var empty=document.createElement('div');
  empty.className='cx-empty'; empty.hidden=true;
  empty.innerHTML='<strong>Nothing matches that</strong>'+
    '<p>Try clearing the filters, or look in another school. If what you want isn&rsquo;t on the '+
    'site yet, <a href="contact" style="color:var(--orange-deep);font-weight:700;'+
    'text-decoration:underline">ask HR</a> — if it&rsquo;s in the catalogue, you can do it.</p>';
  var last=grids[grids.length-1];
  last.parentNode.insertBefore(empty,last.nextSibling);

  function show(el,on){ if(el) el.style.display=on?'':'none'; }

  function apply(){
    var searching=!!state.q;
    var open=searching||!!state.school;   // is anything but the chooser on screen?

    show(intro,!open);
    show(chooser,!open);

    if(open){
      head.hidden=false;
      if(searching){
        headEyebrow.textContent='Search';
        headTitle.textContent='Results for “'+q.value.trim()+'”';
        headBlurb.textContent='Across all four schools.';
      }else{
        headEyebrow.textContent='School';
        headTitle.textContent=SCHOOLS[state.school].name;
        headBlurb.textContent=SCHOOLS[state.school].blurb;
      }
    }else{
      head.hidden=true;
    }

    /* The credential chips and the running count only say anything over a set
       of cards, so on the chooser they are out of the way. Search stays. */
    show(accRow,open);
    show(count,open);
    show(soon,open);
    box.classList.toggle('cx-lean',!open);

    var shown=0;
    groups.forEach(function(g){
      var onHere=0;
      g.cards.forEach(function(c){
        var okQ=!state.q||(c.dataset.text||'').indexOf(state.q)>=0;
        var okA=state.acc==='all'||(c.dataset.acc||'none')===state.acc;
        var okS=searching||!state.school||(c.dataset.school||'')===state.school;
        var on=open&&okQ&&okA&&okS;
        c.style.display=on?'':'none';
        if(on) onHere++;
      });
      show(g.grid,onHere);
      show(g.label,onHere);
      show(g.note,onHere);
      shown+=onHere;
    });
    empty.hidden=!open||shown>0;

    if(!open) return;

    var total=searching?cards.length
             :cards.filter(function(c){return c.dataset.school===state.school;}).length;
    var where=searching?'across all four schools'
             :'in the '+SCHOOLS[state.school].name;
    var filtered=state.q||state.acc!=='all';
    count.innerHTML='Showing <strong>'+shown+'</strong> of <strong>'+total+
      '</strong> courses '+where+
      (filtered?' <button class="cx-reset" id="cxReset">Clear filters</button>':'')+
      '<br><span style="font-size:12.5px">Ask HR for anything not listed.</span>';
    var r=document.getElementById('cxReset');
    if(r) r.addEventListener('click',function(){
      state.q=''; state.acc='all'; q.value='';
      setActive(accRow,'acc','all');
      apply(); q.focus();
    });
  }

  function setActive(row,key,val){
    row.querySelectorAll('.cx-chip').forEach(function(b){
      b.classList.toggle('on',b.dataset[key]===val);
    });
  }

  /* The open school lives in the URL hash, so a school is a link you can send
     someone and the browser Back button steps out of it for free. #ours and
     #technical are the old in-page band anchors — other pages and older links
     still point at them, so they resolve to the school those bands now sit in. */
  var ALIAS={ours:'business',technical:'engineering'};

  function fromHash(){
    var h=(location.hash||'').replace(/^#/,'').toLowerCase();
    if(ALIAS[h]) h=ALIAS[h];
    return SCHOOLS[h]?h:'';
  }

  function sync(scroll){
    state.school=fromHash();
    if(state.school){ state.q=''; q.value=''; }
    apply();
    if(scroll&&state.school) head.scrollIntoView({behavior:'smooth',block:'start'});
  }

  window.addEventListener('hashchange',function(){ sync(true); });

  /* Back out to the chooser. Clearing the hash with pushState rather than
     location.hash='' avoids the jump to the top of the document that an empty
     fragment causes in some browsers. */
  back.addEventListener('click',function(e){
    e.preventDefault();
    if(history.pushState) history.pushState('',document.title,location.pathname+location.search);
    else location.hash='';
    state.school=''; state.q=''; q.value='';
    apply();
    intro.scrollIntoView({behavior:'smooth',block:'start'});
  });

  q.addEventListener('input',function(){ state.q=q.value.trim().toLowerCase(); apply(); });
  accRow.addEventListener('click',function(e){
    var b=e.target.closest('.cx-chip'); if(!b) return;
    state.acc=b.dataset.acc; setActive(accRow,'acc',state.acc); apply();
  });

  sync(false);
})();

/* ---- External course cards ----
   The international courses are hosted by Google, Microsoft, Coursera and
   Helsinki, so they link straight out. Giving them a SPS course page would
   mean inventing modules, videos and workbooks we don't own — the outbound
   link is the honest version. cards.js only rewires cards whose titles it
   recognises, so it leaves these alone. */
(function(){
  [].slice.call(document.querySelectorAll('.ccard[data-href]')).forEach(function(c){
    if(c.dataset.locked) return;   // locked by locks.js — don't link it out either
    var url=c.dataset.href, h=c.querySelector('h4');
    if(h&&!h.querySelector('a')){
      h.innerHTML='<a href="'+url+'" target="_blank" rel="noopener noreferrer" '+
        'style="color:inherit">'+h.innerHTML+'</a>';
    }
    c.style.cursor='pointer';
    c.addEventListener('click',function(e){
      if(e.target.closest('a,button')) return;   // let the link and the badge do their own thing
      window.open(url,'_blank','noopener');
    });
  });
})();

/* ---- Accreditation badge popover ----
   Hover covers pointer users; click/Enter is what makes it reachable on touch
   and by keyboard, which a hover-only tooltip never is. */
(function(){
  var badges=[].slice.call(document.querySelectorAll('.acc-badge'));
  if(!badges.length) return;
  function closeAll(except){
    badges.forEach(function(b){ if(b!==except) b.setAttribute('aria-expanded','false'); });
  }
  badges.forEach(function(b){
    b.setAttribute('aria-expanded','false');
    b.addEventListener('click',function(e){
      e.preventDefault(); e.stopPropagation();
      var open=b.getAttribute('aria-expanded')==='true';
      closeAll(b);
      b.setAttribute('aria-expanded',open?'false':'true');
    });
  });
  document.addEventListener('click',function(){ closeAll(null); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeAll(null); });
})();
