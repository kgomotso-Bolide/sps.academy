  /* ---- AI in Action: typewriter demo ---- */
  (function(){
    const chat=document.getElementById('aiChat'),tabs=document.getElementById('aiTabs');if(!chat||!tabs)return;
    const SCENES=[
      {u:"Draft an email to a customer whose system underproduced last month — two strings were offline and we've now repaired them.",
       b:"<b>Subject: Your October generation — strings repaired</b><br><br>Hi Nomsa,<br><br>Your system generated less than usual in October because <b>two strings on the north array were offline</b> from the 8th, following an isolator fault. Our team repaired it on the 29th and the array is back at full output.<br><br>The shortfall works out to roughly <b>410 kWh</b>. Your maintenance plan covers the repair in full, and we'll keep a closer watch on that inverter for the next two cycles.<br><br>Kind regards,<br>SPS Customer Care"},
      {u:"Summarise this month's performance report into 3 points for the ops meeting.",
       b:"Three things worth raising:<br><br>• <b>Fleet performance ratio 81.4%</b> — up from 76% since the Rosslyn arrays were cleaned.<br>• <b>Two inverters at Midrand still derating</b> above 38 °C — worth checking ventilation before summer.<br>• <b>17 sites below 90% of modelled yield</b>, 12 of them soiling-related — a cleaning cycle would recover most of it."},
      {u:"Here's 5,000 rows of half-hourly generation data. Which arrays look wrong?",
       b:"Three anomalies stand out:<br><br>• <b>9 sites generating zero for 14+ days</b> in clear weather — likely tripped isolators or comms failures.<br>• <b>Inverter 3 at Centurion peaks at 62% of nameplate</b> every day from 11:00 — that pattern is shading, not a fault.<br>• <b>Kempton Park is 13% below</b> its neighbours on the same irradiance — check string voltages before you send anyone out."},
      {u:"Plan a 6-week rollout to install rooftop PV across a 480-unit estate.",
       b:"Here's a phased plan:<br><br>• <b>Wk 1:</b> Roof survey + structural sign-off; confirm DB positions and access windows.<br>• <b>Wk 2–4:</b> Two teams, 12 roofs a day, each commissioned and registered to the monitoring platform same-day.<br>• <b>Wk 5:</b> Resident onboarding — the app, generation reporting, WhatsApp help line.<br>• <b>Wk 6:</b> Reconcile modelled vs actual yield, close out snags, hand over to the maintenance desk."}
    ];
    let active=-1,timer=null;
    const esc=s=>s.replace(/&/g,'&amp;').replace(/</g,'&lt;');
    function clearTimers(){if(timer){clearTimeout(timer);timer=null;}}
    function typeHTML(el,html,done){
      // tokens are either HTML tags (injected whole) or text runs (typed char-by-char)
      const tokens=html.match(/<[^>]+>|[^<]+/g)||[];
      const caret='<span class="ai-caret"></span>';
      let ti=0,ci=0,built='';
      (function step(){
        if(ti>=tokens.length){el.innerHTML=html;done&&done();return;}
        const tk=tokens[ti];
        if(tk[0]==='<'){built+=tk;ti++;ci=0;el.innerHTML=built+caret;timer=setTimeout(step,12);return;}
        ci++;
        el.innerHTML=built+tk.slice(0,ci)+caret;
        if(ci>=tk.length){built+=tk;ti++;ci=0;}
        timer=setTimeout(step,tk.length>40?7:16);
      })();
    }
    function run(i){
      if(i===active)return;active=i;clearTimers();chat.innerHTML='';
      [...tabs.children].forEach((b,bi)=>b.classList.toggle('active',bi===i));
      const sc=SCENES[i];
      const u=document.createElement('div');u.className='ai-msg user';
      u.innerHTML='<div class="av">You</div><div class="bubble">'+sc.u+'</div>';
      chat.appendChild(u);
      timer=setTimeout(()=>{
        const b=document.createElement('div');b.className='ai-msg bot';
        b.innerHTML='<div class="av">AI</div><div class="bubble"></div>';
        chat.appendChild(b);
        typeHTML(b.querySelector('.bubble'),sc.b);
      },450);
    }
    tabs.addEventListener('click',e=>{const t=e.target.closest('.ai-tab');if(t)run(+t.dataset.i);});
    // start when section scrolls into view
    new IntersectionObserver((es,ob)=>es.forEach(e=>{if(e.isIntersecting){run(0);ob.disconnect();}}),{threshold:.35}).observe(chat);
  })();

  /* ---- Animated stat counters ---- */
  (function(){
    const nums=[...document.querySelectorAll('.ai-stats .num')];if(!nums.length)return;
    const obs=new IntersectionObserver((es,ob)=>es.forEach(e=>{
      if(!e.isIntersecting)return;ob.unobserve(e.target);
      const el=e.target,txt=el.dataset.txt;
      if(txt){el.textContent=txt;return;}
      const to=+el.dataset.to,suf=el.dataset.suf||'';let s=null;
      (function tick(t){if(!s)s=t;const p=Math.min((t-s)/1100,1);el.textContent=Math.round(p*to)+suf;if(p<1)requestAnimationFrame(tick);})(performance.now());
    }),{threshold:.6});
    nums.forEach(n=>obs.observe(n));
  })();

