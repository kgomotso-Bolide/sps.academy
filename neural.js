  /* ---- Hero neural-network canvas ----
     PAUSED 2026-08-04: Kgomotso finds the drifting motion uncomfortable, so
     the hero draws one still frame and stops — same texture behind the
     headline, nothing moving. Set PAUSED=false to bring the drift back.
     (The scroll-reveal and the pulsing live dot are held still too — see
     the REVEAL note near the end of styles.css.) */
  (function(){
    const PAUSED=true;

    const cv=document.getElementById('neural');if(!cv)return;
    const ctx=cv.getContext('2d');let w,h,nodes,raf;
    const COLORS=['#c7c4c9','#c7c4c9','#c7c4c9','#b9b6bb','#f37424','#4da446']; // mostly grey, rare orange/green
    function size(){
      const r=cv.parentElement.getBoundingClientRect();w=cv.width=r.width;h=cv.height=r.height;build();
      /* Sizing the canvas clears it, so when there's no animation loop running
         to redraw it, this is what puts the still frame up — on first call and
         on every resize after. */
      if(still) paint();
    }
    function build(){
      const n=Math.min(46,Math.round(w*h/26000));
      nodes=Array.from({length:n},()=>({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-.5)*.35,vy:(Math.random()-.5)*.35,c:COLORS[Math.floor(Math.random()*COLORS.length)]}));
    }
    /* paint() renders the current positions; step() is what advances them.
       Split so a still frame is a paint with no step. */
    function paint(){
      ctx.clearRect(0,0,w,h);
      for(let i=0;i<nodes.length;i++){
        const a=nodes[i];
        for(let j=i+1;j<nodes.length;j++){
          const b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=Math.hypot(dx,dy);
          if(d<130){ctx.strokeStyle='rgba(120,120,130,'+(1-d/130)*.28+')';ctx.lineWidth=.7;ctx.beginPath();ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);ctx.stroke();}
        }
      }
      nodes.forEach(a=>{ctx.fillStyle=a.c;ctx.globalAlpha=.7;ctx.beginPath();ctx.arc(a.x,a.y,2.2,0,7);ctx.fill();ctx.globalAlpha=1;});
    }
    function step(){
      nodes.forEach(a=>{
        a.x+=a.vx;a.y+=a.vy;
        if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;
      });
    }
    function draw(){step();paint();raf=requestAnimationFrame(draw);}

    const reduce=window.matchMedia('(prefers-reduced-motion:reduce)').matches;
    const still=PAUSED||reduce;
    size();window.addEventListener('resize',size);
    if(!still) draw();
  })();
