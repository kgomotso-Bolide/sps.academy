/* ---- Show / Hide on every password field (28 Oct 2026) ----
   Asked for so a password can be read back before it is submitted, or copied
   out of a new-password box. One file, loaded by every page with a password
   field (login, reset, invite, my, setup), and synced from SPS — site.js is
   not shared between the academies, so it could not live there.

   The button is type="button" so it never submits the form, and every field
   goes back to type="password" on submit: a browser that remembers form
   history keeps what was typed into TEXT inputs, and a password should not be
   one of them. Without JavaScript the fields are ordinary password fields. */
(function(){
  var fields=document.querySelectorAll('input[type="password"]');
  for(var i=0;i<fields.length;i++) add(fields[i]);

  function add(input){
    var wrap=document.createElement('span');
    wrap.className='pw-wrap';
    input.parentNode.insertBefore(wrap,input);
    wrap.appendChild(input);

    var btn=document.createElement('button');
    btn.type='button';
    btn.className='pw-toggle';
    btn.textContent='Show';
    btn.setAttribute('aria-pressed','false');
    btn.setAttribute('aria-label','Show password');
    if(input.id) btn.setAttribute('aria-controls',input.id);
    wrap.appendChild(btn);

    btn.addEventListener('click',function(){
      var show=input.type==='password';
      set(input,btn,show);
      input.focus();
    });
    if(input.form) input.form.addEventListener('submit',function(){ set(input,btn,false); });
  }

  function set(input,btn,show){
    input.type=show?'text':'password';
    btn.textContent=show?'Hide':'Show';
    btn.setAttribute('aria-pressed',show?'true':'false');
    btn.setAttribute('aria-label',show?'Hide password':'Show password');
  }
})();
