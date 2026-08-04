/* ---- Profile page: hydrate the form, render the snapshot panels ----
   Only runs on profile.html. The store itself lives in profile.js. */
(function(){
  var form=document.getElementById('pf-form'); if(!form) return;
  var P=window.SPSProfile;

  var FIELDS=['name','empno','dept','manager','email','phone','role','years','qual','bg'];
  function el(k){ return document.getElementById('pf-'+k); }

  var ROLE_NAMES={install:'Solar Installer / PV Technician',field:'Field Operations & Maintenance',sales:'Sales & Projects',
    care:'Customer Care',datasys:'Data & Systems',lead:'Team Lead / Supervisor',
    manager:'Manager / Head of Department',support:'Admin & Support'};

  /* ---- storage availability ---- */
  if(!P.available()){
    document.getElementById('pf-warn').innerHTML=
      '<div class="sg-card" style="border-color:#b23c17">'+
      '<h3>This browser won\'t let us save</h3>'+
      '<p class="sg-intro" style="margin-bottom:0">Storage is blocked — usually private/incognito mode, or a browser '+
      'setting. You can still fill the form in, but nothing will be kept once you leave the page. Everything else on '+
      'the site works normally.</p></div>';
  }

  /* ---- hydrate ---- */
  var p=P.get();
  FIELDS.forEach(function(k){ var e=el(k); if(e&&p[k]) e.value=p[k]; });

  var greet=document.getElementById('pf-greet');
  if(P.exists()&&P.firstName()) greet.textContent='Welcome back, '+P.firstName();

  /* ---- save ---- */
  form.addEventListener('submit',function(ev){
    ev.preventDefault();
    var patch={};
    FIELDS.forEach(function(k){ var e=el(k); if(e) patch[k]=e.value.trim?e.value.trim():e.value; });
    var ok=P.save(patch);
    var flag=document.getElementById('pf-saved');
    flag.hidden=false;
    flag.textContent=ok?'Saved to this device':'Could not save — storage is blocked';
    flag.classList.toggle('bad',!ok);
    if(ok&&P.firstName()) greet.textContent='Welcome back, '+P.firstName();
    window.dispatchEvent(new Event('sps-profile-changed'));
    setTimeout(function(){ flag.hidden=true; },4000);
  });

  /* ---- skills snapshot ---- */
  function renderSkills(){
    var body=document.getElementById('pf-skills-body');
    var s=P.get().skills;
    if(!s||!s.rows||!s.rows.length){
      body.innerHTML='<p class="sg-intro">You haven\'t run the Skills Gap check yet. It takes about two minutes and '+
        'tells you where you sit against the target for your role.<br><br>'+
        '<a class="btn btn-primary" href="skills-gap">Run the Skills Gap check</a></p>';
      return;
    }
    var gaps=s.rows.filter(function(r){return r.gap>0;}).sort(function(a,b){return b.gap-a.gap;});
    var when=s.date?new Date(s.date):null;
    body.innerHTML=
      '<p class="sg-intro">Saved from your Skills Gap check'+
        (when?' on '+when.toLocaleDateString('en-ZA',{day:'numeric',month:'long',year:'numeric'}):'')+
        (s.role?', against a <strong>'+s.role+'</strong>':'')+'.</p>'+
      '<div class="gapchart">'+ s.rows.map(function(r){
        var cls=r.gap<=0?'ok':(r.gap===1?'mid':'hi'), txt=r.gap<=0?'On track':(r.gap===1?'Small gap':'Priority');
        return '<div class="gaprow">'+
          '<div class="gaplabel">'+r.n+'</div>'+
          '<div class="gaptrack"><div class="gapbar" style="width:'+(r.self/4*100)+'%"></div>'+
          '<div class="gapmark" style="left:'+(r.target/4*100)+'%"></div></div>'+
          '<div class="gapstat '+cls+'">'+txt+'</div></div>';
      }).join('') +'</div>'+
      '<p class="sg-priv">'+(gaps.length
        ? 'Biggest gap: <strong>'+gaps[0].n+'</strong>. '
        : 'You were at or above target on everything. ')+
      '<a href="skills-gap">Run it again</a> to update this.</p>';
  }

  /* ---- saved courses ---- */
  function renderCourses(){
    var body=document.getElementById('pf-courses-body');
    var list=P.get().courses||[];
    if(!list.length){
      body.innerHTML='<p class="sg-intro">Nothing saved yet. Open any course and hit <strong>Save to my profile</strong> '+
        'to keep it here.<br><br><a class="btn btn-primary" href="courses">Browse courses</a></p>';
      return;
    }
    body.innerHTML='<div class="pf-courselist">'+ list.map(function(c){
      return '<div class="pf-course"><a href="course?c='+encodeURIComponent(c.slug)+'">'+c.title+'</a>'+
             '<button class="pf-drop" data-slug="'+c.slug+'" aria-label="Remove '+c.title+'">Remove</button></div>';
    }).join('') +'</div>'+
    '<div class="sg-actions"><a class="btn btn-primary" href="contact?course='+
      encodeURIComponent(list[0].title)+'">Register for '+(list.length>1?'the first one':'it')+'</a></div>';

    body.querySelectorAll('.pf-drop').forEach(function(b){
      b.addEventListener('click',function(){
        P.save({courses:(P.get().courses||[]).filter(function(c){return c.slug!==b.dataset.slug;})});
        renderCourses();
      });
    });
  }

  /* ---- delete ---- */
  var clearBtn=document.getElementById('pf-clear');
  clearBtn.addEventListener('click',function(){
    if(clearBtn.dataset.armed!=='1'){
      clearBtn.dataset.armed='1';
      clearBtn.textContent='Tap again to delete everything';
      setTimeout(function(){
        if(clearBtn.dataset.armed==='1'){ clearBtn.dataset.armed=''; clearBtn.textContent='Delete my profile'; }
      },5000);
      return;
    }
    P.clear();
    FIELDS.forEach(function(k){ var e=el(k); if(e) e.value=''; });
    greet.textContent='Save your details once';
    clearBtn.dataset.armed=''; clearBtn.textContent='Delete my profile';
    renderSkills(); renderCourses();
    var flag=document.getElementById('pf-saved');
    flag.hidden=false; flag.classList.remove('bad'); flag.textContent='Profile deleted from this device';
    setTimeout(function(){ flag.hidden=true; },4000);
    window.dispatchEvent(new Event('sps-profile-changed'));
  });

  document.getElementById('pf-print').addEventListener('click',function(){ window.print(); });

  renderSkills();
  renderCourses();
})();
