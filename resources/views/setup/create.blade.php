<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Install Jelly | Setup</title>
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
</head>
<body>
  <main class="password-screen">
    <section class="password-card">
      <span class="auth-brand"><span class="brand-mark">◎</span>Jelly</span>
      <p class="eyebrow">FIRST-RUN INSTALLATION</p>
      <h1>Set up your clinic</h1>
      <p>Create the Super Admin account for this deployment. You can configure outgoing email afterwards in Admin → Settings.</p>
      <form method="POST" action="{{ route('setup.store') }}">
        @csrf
        <label class="field">Clinic / company name<input name="clinic_name" type="text" value="{{ old('clinic_name') }}" required maxlength="120" autofocus></label>
        <label class="field">Super Admin name<input name="name" type="text" value="{{ old('name') }}" required maxlength="120"></label>
        <label class="field">Super Admin email<input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="username"></label>
        <label class="field">Password<input name="password" type="password" required minlength="12" autocomplete="new-password"></label>
        <label class="field">Confirm password<input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></label>
        @foreach ($errors->all() as $error)
          <p class="form-error" role="alert">{{ $error }}</p>
        @endforeach
        <button class="btn primary" type="submit">Complete installation</button>
      </form>
    </section>
  </main>
</body>
</html>
