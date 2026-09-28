<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barriecito&family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <title>Fan Hub Plus — Admin Login</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-login.css">
</head>
<body class="login-page">
    <main class="login-card" data-aos="zoom-in" data-aos-duration="700" data-aos-easing="ease-out-cubic">
        <div class="login-brand"><span>FanHubPlus</span></div>
        <p class="eyebrow">Admin workspace</p>
        <h1>Welcome back</h1>
        <p class="login-copy">Sign in to manage the Fan Hub Plus universe.</p>
        <form id="adminLoginForm" action="{{ route('admin.login.submit') }}" method="post" novalidate>
            @csrf
            <label>Email<input type="email" name="email" value="admin@fanhubplus.com" required autocomplete="username"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <p class="login-error" id="loginError" role="alert">@error('email'){{ $message }}@enderror</p>
            <button class="primary login-submit" type="submit">Sign in</button>
        </form>
    </main>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>window.AOS&&window.AOS.init({duration:700,easing:'ease-out-cubic',once:true});</script>
    <script src="/assets/js/admin-login.js"></script>
</body>
</html>
