/* Shared site behaviour: nav toggle, scroll shadow, scroll-reveal */
(function(){
  var nav=document.getElementById('nav'),toggle=document.getElementById('navToggle'),links=document.getElementById('navLinks');
  if(toggle&&links){
    toggle.addEventListener('click',function(){links.classList.toggle('open');});
    links.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){links.classList.remove('open');});});
  }
  window.addEventListener('scroll',function(){ if(nav) nav.classList.toggle('scrolled',window.scrollY>10); });
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}});},{threshold:.12});
  document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});

  /* Prefill the course field when arriving from a Skills Gap recommendation
     (contact?course=AI+Fundamentals…) so nobody has to retype the title. */
  var course=new URLSearchParams(location.search).get('course');
  if(course){
    var f=document.querySelector('input[name="Course"]');
    if(f){ f.value=course; f.setAttribute('data-prefilled','1'); }
  }
})();
