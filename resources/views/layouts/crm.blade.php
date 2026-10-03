<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Jelly care team workspace">
  <title>{{ $title ?? 'Care workspace · Jelly' }}</title>
  <base href="{{ $apiBase }}/">
  <link rel="stylesheet" href="{{ route('adhd.crm.styles') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/styles.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets/ADHD-CRM/css/clinician-workspace.css') }}?v={{ filemtime(public_path('assets/ADHD-CRM/css/clinician-workspace.css')) }}">
  <script src="{{ route('adhd.crm.scripts') }}?v={{ filemtime(public_path('assets/ADHD-CRM/js/script.js')) }}" defer></script>
</head>
<body class="crm-panel-{{ $panel }}">
  <a class="skip" id="skipLink" href="#main">Skip to workspace</a>

  <div id="workspace">
    @include('components.crm.sidebar', [
        'workspaceLabel' => $workspaceLabel,
        'workspaceDetail' => $workspaceDetail,
        'user' => $user,
        'userInitials' => $userInitials,
        'userAvatarUrl' => $userAvatarUrl ?? null,
        'roleLabel' => $roleLabel,
    ])

    <button class="sidebar-overlay" id="sidebarOverlay" aria-label="Close menu" hidden></button>

    <div class="app-shell">
      @include('components.crm.topbar', [
          'workspacePill' => $workspacePill,
          'userInitials' => $userInitials,
          'userAvatarUrl' => $userAvatarUrl ?? null,
      ])

      <main id="main" tabindex="-1"></main>

      <footer class="workspace-footer">
        <span><i class="live-dot"></i> Every next step, in one place</span>
        <span>Jelly · Care workspace</span>
      </footer>
    </div>
  </div>

  @include('components.crm.shell-dialogs')

  @include('components.crm.workspace-boot', [
      'panel' => $panel,
      'apiBase' => $apiBase,
      'navigation' => $navigation,
      'user' => $user,
      'canManageClinicians' => $canManageClinicians,
      'canAccessSettings' => $canAccessSettings ?? false,
  ])
</body>
</html>
