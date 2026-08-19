/* ---- Profile page: hydrate the form, render the snapshot panels ----
   Only runs on profile.html. The store itself lives in profile.js. */
(function(){
  var form=document.getElementById('pf-form'); if(!form) return;
  var P=window.SPSProfile;

  var FIELDS=['name','empno','dept','manager','email','phone'];
  function el(k){ return document.getElementById('pf-'+k); }


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
    renderCourses();
    var flag=document.getElementById('pf-saved');
    flag.hidden=false; flag.classList.remove('bad'); flag.textContent='Profile deleted from this device';
    setTimeout(function(){ flag.hidden=true; },4000);
    window.dispatchEvent(new Event('sps-profile-changed'));
  });

  document.getElementById('pf-print').addEventListener('click',function(){ window.print(); });

  renderCourses();
})();
