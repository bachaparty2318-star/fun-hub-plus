const memberLoginForm = document.querySelector('#memberLoginForm');

document.querySelectorAll('.password-toggle').forEach(toggle => {
  toggle.addEventListener('click', () => {
    const input = toggle.closest('.password-field')?.querySelector('input');
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  });
});

memberLoginForm?.addEventListener('submit', async event => {
  event.preventDefault();
  const error = document.querySelector('#memberLoginError');
  const button = memberLoginForm.querySelector('button[type="submit"]');
  const mode = memberLoginForm.dataset.mode || 'login';
  error.textContent = '';
  button.disabled = true;
  try {
    const csrf = await fetch('/user/api/auth/csrf', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    const token = (await csrf.json()).csrf_token;
    const data = Object.fromEntries(new FormData(memberLoginForm).entries());
    if (mode === 'reset') data.token = memberLoginForm.dataset.token;
    const endpoint = mode === 'register'
      ? '/user/api/auth/register'
      : mode === 'forgot'
        ? '/user/api/auth/forgot-password'
        : mode === 'reset'
          ? '/user/api/auth/reset-password'
          : '/user/api/auth/login';
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(data),
    });
    const json = await response.json().catch(() => ({}));
    if (!response.ok) throw Error(json.message || Object.values(json.errors || {}).flat()[0] || 'Please check your details and try again.');
    if (mode === 'forgot') {
      error.textContent = json.message || 'If the account exists, a reset link has been sent.';
      button.disabled = false;
      return;
    }
    if (mode === 'reset') {
      location.href = '/user/login';
      return;
    }
    location.href = '/user';
  } catch (e) {
    error.textContent = e.message || 'Unable to continue.';
    button.disabled = false;
  }
});
