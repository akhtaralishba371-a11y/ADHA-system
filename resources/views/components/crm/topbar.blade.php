<header class="topbar">
  <div class="breadcrumb">
    <button id="menuToggle" class="icon-button mobile-menu" type="button" aria-label="Open menu"></button>
    <span>Workspace</span>
    <span class="slash">/</span>
    <strong id="breadcrumb">Overview</strong>
  </div>
  <div class="top-actions">
    <span class="workspace-pill"><i></i> {{ $workspacePill }}</span>
    @unless (app()->isProduction())
      <button class="icon-button" id="guideButton" type="button" aria-label="Workspace guide" title="Workspace guide"></button>
    @endunless
    <button class="icon-button" id="resetButton" type="button" aria-label="Refresh workspace" title="Refresh workspace"></button>
    <button class="icon-button" id="signOutButton" type="button" aria-label="Sign out" title="Sign out" data-icon="logout"></button>
    @if ($userAvatarUrl ?? null)
      <img class="avatar top-avatar avatar-image" src="{{ $userAvatarUrl }}" alt="">
    @else
      <span class="avatar top-avatar">{{ $userInitials }}</span>
    @endif
  </div>
</header>
