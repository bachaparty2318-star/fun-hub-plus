(function(){
  function applyAos(){
    const items=document.querySelectorAll('.page-head,.pipeline-zone,.events-panel,.data-card,.stat,.pipeline-item,.event-item,.account-section,.modal,.quickbar button');
    items.forEach((element,index)=>{
      if(!element.dataset.aos)element.dataset.aos='fade-up';
      element.dataset.aosDuration=element.dataset.aosDuration||'650';
      element.dataset.aosDelay=element.dataset.aosDelay||String(Math.min(index%6,5)*35);
      element.dataset.aosOnce='true';
    });
    if(window.AOS){window.AOS.init({duration:650,easing:'ease-out-cubic',offset:55,once:true,mirror:false});window.AOS.refreshHard();}
  }
  applyAos();
  setInterval(applyAos,1200);
  let searchTimer;
  const currentKey=()=>location.hash.slice(1)||'dashboard';
  const reload=()=>{const key=currentKey();if(window.resources?.[key])window.loadResource(key)};
  document.addEventListener('input',event=>{
    if(!event.target.matches('#resourceSearch,#globalSearch'))return;
    clearTimeout(searchTimer);
    searchTimer=setTimeout(()=>{
      if(event.target.id==='globalSearch'&&currentKey()==='dashboard'){
        location.hash='content';
        setTimeout(()=>{const input=document.querySelector('#resourceSearch');if(input){input.value=event.target.value;window.loadResource('content')}},80);
      }else reload();
    },250);
  });
  document.addEventListener('change',event=>{if(event.target.matches('#filters [data-filter]'))reload()});
  const mediaUrl=url=>String(url||'').replace('/storage/','/media/');
  const initials=name=>(String(name||'Admin').trim().split(/\s+/).map(part=>part[0]).join('').slice(0,2)||'A').toUpperCase();
  function setAvatar(url,name){
    const avatar=document.querySelector('#avatar');
    const accountAvatar=document.querySelector('#accountAvatar');
    const value=mediaUrl(url);
    const fallback=element=>{if(element)element.innerHTML=`<span>${initials(name)}</span>`};
    const render=(element,label)=>{if(!element)return;if(!value){fallback(element);return}element.innerHTML=`<img src="${value}" alt="${label}">`;const image=element.querySelector('img');image.addEventListener('error',()=>fallback(element),{once:true})};
    render(avatar,'Admin profile picture');
    render(accountAvatar,'Admin profile picture');
  }
  async function enhanceAccount(){
    const card=document.querySelector('#page-resource[data-resource-key="account"] .account-section');
    if(!card||card.querySelector('#adminAvatarForm'))return;
    const block=document.createElement('div');
    block.className='account-avatar-block';
    block.innerHTML='<form id="adminAvatarForm"><label>Profile Picture<input id="adminAvatarInput" type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label><button class="secondary" type="submit">Update Profile Picture</button><small class="avatar-upload-error" aria-live="polite"></small></form>';
    card.append(block);
    try{const result=await window.adminApi('auth/me');const user=result.data||result;setAvatar(user.profile?.avatar_url,user.name);}
    catch(error){setAvatar('',document.querySelector('#adminName')?.textContent)}
    document.querySelector('#adminAvatarForm').onsubmit=async event=>{event.preventDefault();const form=event.currentTarget;const input=form.querySelector('input[type=file]');const errorBox=form.querySelector('.avatar-upload-error');errorBox.textContent='';if(!input.files?.[0]){errorBox.textContent='Choose a JPG, PNG or WEBP image first.';return}const data=new FormData(form);try{const token=await window.adminCsrf();const response=await fetch('/admin/api/auth/avatar',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},body:data});const result=await response.json().catch(()=>({}));if(!response.ok){const message=result.message||Object.values(result.errors||{}).flat()[0]||'Profile picture upload failed.';throw Error(message)}setAvatar(result.data.avatar_url,document.querySelector('#adminName')?.textContent);form.reset();toast('Profile picture updated successfully')}catch(error){errorBox.textContent=error.message}};
  }
  setInterval(()=>{enhanceAccount();const key=currentKey();if(key==='activity'||key==='users')reload()},10000);
  window.addEventListener('hashchange',()=>setTimeout(enhanceAccount,100));
  enhanceAccount();
})();
(function(){
  function badge(){const link=document.querySelector('.top-shortcuts a[href="#submissions"]');if(!link)return null;let el=link.querySelector('.admin-notification-badge');if(!el){el=document.createElement('span');el.className='admin-notification-badge';link.append(el)}return el}
  async function refresh(){try{const result=await window.adminApi('dashboard');const data=result.data||result;const count=Number(data.pending_submissions||0);const el=badge();if(el){el.textContent=count>99?'99+':String(count);el.hidden=count<1}}catch(error){}}
  refresh();setInterval(refresh,15000);
})();
