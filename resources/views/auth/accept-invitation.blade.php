<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Activate clinician account | Jelly</title>
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
</head>
<body>
  <main class="password-screen">
    <section class="password-card">
      <a class="auth-brand" href="{{ route('staff.login') }}"><span class="brand-mark">◎</span>Jelly</a>
      <p class="eyebrow">CLINICIAN INVITATION</p>
      <h1>Activate your clinician account</h1>
      <p>Choose a password to finish joining the Jelly care workspace. This invitation expires in {{ $expiresInMinutes }} minutes and can only be used once.</p>
      <form method="POST" action="{{ route('staff.invitation.store') }}">
        @csrf
        <input name="token" type="hidden" value="{{ $token }}">
        <input name="email" type="hidden" value="{{ $email }}">
        <label class="field">Email address<input type="email" value="{{ $email }}" disabled></label>
        <label class="field">Password<input name="password" type="password" required autocomplete="new-password" minlength="12"></label>
        <label class="field">Confirm password<input name="password_confirmation" type="password" required autocomplete="new-password" minlength="12"></label>
        @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        @error('password')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        <button class="btn primary" type="submit">Activate account</button>
      </form>
    </section>
  </main>
</body>
</html>
