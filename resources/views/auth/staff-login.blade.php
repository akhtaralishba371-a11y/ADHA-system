<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Staff sign in | Jelly</title>
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
</head>
<body>
  <main class="login-screen" aria-labelledby="loginTitle">
    <section class="login-story">
      <div class="brand"><span class="brand-mark">◎</span><span>Jelly<small>CARE WORKSPACE</small></span></div>
      <div class="login-story-copy">
        <span class="login-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg></span>
        <p class="eyebrow">SECURE STAFF ACCESS</p>
        <h2>A little clarity.<br>A more connected<br>day of care.</h2>
        <p>Your patients, your team and every next step. Together in one protected workspace.</p>
      </div>
      <p class="login-story-footer">Authorised staff only · Sessions are protected and audited</p>
    </section>
    <section class="login-main">
      <div class="login-card">
        <span class="login-lock" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg></span>
        <p class="eyebrow">CARE TEAM WORKSPACE</p>
        <h1 id="loginTitle">Welcome back</h1>
        <p class="login-subtitle">Sign in with your authorised staff account.</p>
        <form method="POST" action="{{ route('staff.login.store') }}">
          @csrf
          <label class="field">Email address<input name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="you@clinic.com"></label>
          <label class="field">Password
            <span class="password-field">
              <input id="loginPassword" name="password" type="password" autocomplete="current-password" required>
              <button class="icon-button" id="passwordToggle" type="button" aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </span>
          </label>
          @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
          <div class="login-links">
            <label class="remember"><input name="remember" type="checkbox" value="1"> Keep me signed in</label>
            <a href="{{ route('staff.password.request') }}">Forgot your password?</a>
          </div>
          <button class="btn primary login-submit" type="submit">Sign in to workspace</button>
        </form>
        <p class="login-disclaimer">Only use this sign-in on a private device. Contact your administrator if you need access.</p>
      </div>
    </section>
  </main>
  <script>
    (function () {
      var input = document.getElementById('loginPassword');
      var button = document.getElementById('passwordToggle');
      if (!input || !button) return;
      var eye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
      var eyeOff = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.8 5.2A12 12 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.8M6.4 6.4A19 19 0 0 0 2 12s3.5 7 10 7a13 13 0 0 0 5.6-1.6"/></svg>';
      button.addEventListener('click', function () {
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(visible));
        button.innerHTML = visible ? eyeOff : eye;
      });
    })();
  </script>
</body>
</html>
