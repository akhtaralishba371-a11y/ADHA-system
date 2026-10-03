<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Choose a password | Jelly</title>
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
</head>
<body>
  <main class="password-screen">
    <section class="password-card">
      <a class="auth-brand" href="{{ route('staff.login') }}"><span class="brand-mark">◎</span>Jelly</a>
      <p class="eyebrow">STAFF ACCOUNT</p>
      <h1>Choose a new password</h1>
      <p>Pick a strong password you don’t use anywhere else.</p>
      <form method="POST" action="{{ route('staff.password.update') }}">
        @csrf
        <input name="token" type="hidden" value="{{ $token }}">
        <input name="email" type="hidden" value="{{ $email }}">
        <label class="field">New password<input name="password" type="password" required autocomplete="new-password" autofocus></label>
        <label class="field">Confirm password<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        @error('password')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        <button class="btn primary" type="submit">Update password</button>
      </form>
    </section>
  </main>
</body>
</html>
