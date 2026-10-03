<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Reset password | Jelly</title>
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
</head>
<body>
  <main class="password-screen">
    <section class="password-card">
      <a class="auth-brand" href="{{ route('staff.login') }}"><span class="brand-mark">◎</span>Jelly</a>
      <p class="eyebrow">STAFF ACCOUNT</p>
      <h1>Reset your password</h1>
      <p>Enter your staff email address and we will send a reset link if an account exists.</p>
      @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
      <form method="POST" action="{{ route('staff.password.email') }}">
        @csrf
        <label class="field">Email address<input name="email" type="email" required autocomplete="email" placeholder="you@clinic.com" autofocus></label>
        @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        <button class="btn primary" type="submit">Send reset link</button>
      </form>
      <p class="auth-footer"><a href="{{ route('staff.login') }}">Back to sign in</a></p>
    </section>
  </main>
</body>
</html>
