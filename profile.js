/* ---- SPS Academy profile (local only) ----
   No account, no password, no backend. The profile lives in this browser's
   localStorage and never leaves the device — same reasoning as the Skills Gap
   tool: it holds employment details, which are POPIA-regulated, and this site
   is on public static hosting with nowhere safe to put them.

   The difference from Skills Gap is that this one PERSISTS. On a shared or
   site machine the next person would see it, which is why every screen that
   touches the profile offers a visible way to clear it.

   Loaded on every page. It does three jobs:
     1. exposes window.SPSProfile (get/save/clear/has)
     2. upgrades the nav profile link to an initials avatar once one exists
     3. prefills the registration form and the Skills Gap step 1 */
(function(){
  var KEY='sps.profile.v1';

  function read(){
    try{ return JSON.parse(localStorage.getItem(KEY))||null; }catch(e){ return null; }
  }
  function write(o){
    try{ localStorage.setItem(KEY,JSON.stringify(o)); return true; }
    catch(e){ return false; }   // private mode / storage disabled / quota
  }

  var P={
    get:function(){ return read()||{}; },
    exists:function(){ return !!read(); },
    save:function(patch){
      var p=read()||{};
      for(var k in patch) p[k]=patch[k];
      p.updated=new Date().toISOString();
      return write(p);
    },
    clear:function(){ try{ localStorage.removeItem(KEY); }catch(e){} },
    /* Storage can be unavailable (Safari private mode, locked-down browsers).
       Callers need to be able to say so rather than silently losing data. */
    available:function(){
      try{ localStorage.setItem(KEY+'.t','1'); localStorage.removeItem(KEY+'.t'); return true; }
      catch(e){ return false; }
    },
    initials:function(){
      var n=(this.get().name||'').trim();
      if(!n) return '';
      var parts=n.split(/\s+/);
      return ((parts[0]||'')[0]+(parts.length>1?(parts[parts.length-1]||'')[0]:'')).toUpperCase();
    },
    firstName:function(){ return ((this.get().name||'').trim().split(/\s+/)[0])||''; }
  };
  window.SPSProfile=P;

  /* ---- nav avatar ---- */
  function paintNav(){
    var link=document.querySelector('.nav-profile'); if(!link) return;
    var ini=P.initials();
    if(ini){
      var av=link.querySelector('.np-av');
      if(av){ av.textContent=ini; av.classList.add('filled'); }
      var lbl=link.querySelector('.np-label');
      if(lbl) lbl.textContent=P.firstName()||'Profile';
      link.setAttribute('title','Your profile — '+(P.get().name||''));
    }
  }

  /* ---- prefill the registration form ---- */
  function prefillContact(){
    var form=document.querySelector('form.form'); if(!form||!P.exists()) return;
    var p=P.get();
    var map={'Name':p.name,'Employee number':p.empno,'Department':p.dept,
             'Line manager':p.manager,'Email':p.email,'Phone':p.phone};
    var filled=0;
    for(var name in map){
      var el=form.querySelector('[name="'+name+'"]');
      // never overwrite something already there (e.g. ?course= from Skills Gap)
      if(el&&!el.value&&map[name]){ el.value=map[name]; filled++; }
    }
    if(!filled) return;
    var note=document.createElement('p');
    note.className='prefill-note';
    note.innerHTML='Filled in from <a href="profile">your profile</a>. Edit anything that\'s out of date — '+
                   'changing it here won\'t change your profile.';
    form.insertBefore(note,form.querySelector('.two')||form.firstChild);
  }

  /* ---- save-a-course button on the course detail page ---- */
  function wireCourseSave(){
    var btn=document.getElementById('c-save'); if(!btn) return;
    var slug=new URLSearchParams(location.search).get('c'); if(!slug){ btn.remove(); return; }

    function label(){
      var list=P.get().courses||[];
      var on=list.some(function(c){return c.slug===slug;});
      btn.classList.toggle('on',on);
      btn.innerHTML=on
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="m5 12 5 5L20 7"/></svg> Saved to profile'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Save to my profile';
    }
    label();
    btn.addEventListener('click',function(){
      if(!P.available()){
        btn.textContent='Storage is blocked in this browser';
        btn.disabled=true; return;
      }
      var list=(P.get().courses||[]).slice();
      var i=-1; list.forEach(function(c,ix){ if(c.slug===slug) i=ix; });
      if(i>=0) list.splice(i,1);
      else list.push({slug:slug,title:(document.getElementById('c-h1')||{}).textContent||slug});
      P.save({courses:list});
      label();
    });
  }

  function init(){ paintNav(); prefillContact(); wireCourseSave(); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init);
  else init();
})();
