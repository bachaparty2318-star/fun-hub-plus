const form=document.querySelector('#adminLoginForm');
const errorBox=document.querySelector('#loginError');
form.addEventListener('submit',async event=>{
  event.preventDefault();
  errorBox.textContent='';
  const button=form.querySelector('button');
  button.disabled=true;
  try{
    const csrf=await fetch('/admin/api/auth/csrf',{credentials:'same-origin',headers:{Accept:'application/json'}});
    const csrfData=await csrf.json();
    const payload=Object.fromEntries(new FormData(form).entries());
    const response=await fetch('/admin/api/auth/login',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfData.csrf_token},body:JSON.stringify(payload)});
    const data=await response.json();
    if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat()[0]||'Login details are invalid.');
    window.location.href='/admin';
  }catch(error){errorBox.textContent=error.message||'Unable to sign in.';button.disabled=false;}
});
