<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ ucfirst($mode) }} · Fan Hub Plus</title>@include('visitor._styles')</head>
<body>
<nav class="page-nav"><a class="brand" href="/"><span>FanHubPlus</span></a><div class="nav-links"><a href="/">Home</a><a href="/register" class="{{ $mode === 'register' ? 'is-active' : '' }}"><i class="fi fi-rr-user-add"></i> Register</a><a class="{{ $mode === 'login' ? 'is-active' : '' }}" href="/login"><i class="fi fi-rr-sign-in-alt"></i> Login</a></div></nav>
<main class="auth-wrap"><section class="auth-card"><div class="tags"><a class="pill {{ $mode === 'login' ? 'btn alt' : '' }}" href="/login"><i class="fi fi-rr-sign-in-alt"></i> Login</a><a class="pill {{ $mode === 'register' ? 'btn alt' : '' }}" href="/register"><i class="fi fi-rr-user-add"></i> Register</a></div><p class="kicker">Member Access</p><h1 style="font-size:clamp(2.2rem,5vw,4rem)">{{ $mode === 'register' ? 'Create account' : ($mode === 'forgot' ? 'Forgot password' : ($mode === 'reset' ? 'Reset password' : 'Welcome back')) }}</h1>
@if(session('message'))<p class="auth-note">{{ session('message') }}</p>@endif
<form data-auth="{{ $mode }}" data-token="{{ $token }}">
    @if($mode === 'register')<div class="field"><label>Name</label><input name="name" required></div>@endif
    @if($mode !== 'reset')<div class="field"><label>Email</label><input name="email" type="email" required></div>@endif
    @if(in_array($mode, ['login','register','reset']))<div class="field"><label>Password</label><input name="password" type="password" required></div>@endif
    @if($mode === 'reset')<div class="field"><label>Confirm password</label><input name="password_confirmation" type="password" required></div>@endif
    <button class="btn" type="submit">{{ $mode === 'register' ? 'Register' : ($mode === 'forgot' ? 'Send reset link' : ($mode === 'reset' ? 'Reset password' : 'Login')) }}</button>
</form>
<p class="auth-note">@if($mode==='login')<a href="/forgot-password">Forgot password?</a> · <a href="/register">Create account</a>@elseif($mode==='register')<a href="/login">Already have an account?</a>@else<a href="/login">Back to login</a>@endif</p><p id="authMessage" class="auth-note"></p></section></main>
@include('visitor._footer')
<script>
document.querySelector('form').addEventListener('submit', async event => {
 event.preventDefault(); const form = event.currentTarget; const mode = form.dataset.auth; const data = Object.fromEntries(new FormData(form));
 if (mode === 'reset') data.token = form.dataset.token;
 const endpoint = mode === 'register' ? '/visitor/api/auth/register' : mode === 'forgot' ? '/visitor/api/auth/forgot-password' : mode === 'reset' ? '/visitor/api/auth/reset-password' : '/visitor/api/auth/login';
 const res = await fetch(endpoint, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(data)});
 document.getElementById('authMessage').textContent = res.ok ? 'Done. You can continue.' : 'Please check the details and try again.';
 if (res.ok && mode === 'login') location.href = '/user';
});
</script></body></html>
