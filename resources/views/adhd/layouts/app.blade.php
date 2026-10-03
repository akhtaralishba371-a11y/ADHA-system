<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="@yield('meta_description', 'Book an ADHD or autism assessment with Jelly.')">
  <title>@yield('title', 'Jelly')</title>
  <link rel="stylesheet" href="{{ asset('assets/ADHD/css/styles.css') }}">
  @stack('head')
</head>
<body class="@yield('body_class')">
  <a class="skip-link" id="skipLink" href="@yield('skip_href', '#main-content')">@yield('skip_text', 'Skip to content')</a>

  @yield('content')

  @stack('scripts')
</body>
</html>
