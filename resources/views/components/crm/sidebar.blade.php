@php
    /** @var \App\Models\User $user */
@endphp
<aside class="sidebar" id="sidebar">
  <a href="#overview" class="brand">
    <span class="brand-mark">◎</span>
    <span>Jelly<small>CARE WORKSPACE</small></span>
  </a>
  <div class="workspace-switch">
    <span class="workspace-icon">{{ $userInitials }}</span>
    <span>{{ $workspaceLabel }}<small>{{ $workspaceDetail }}</small></span>
    <span class="workspace-dot"></span>
  </div>
  <p class="nav-label">WORKSPACE</p>
  <nav id="navigation" aria-label="Main navigation">
    @foreach ($navigation as $item)
      <a
        href="#{{ $item['id'] }}"
        class="nav-item{{ $loop->first ? ' active' : '' }}"
        @if ($loop->first) aria-current="page" @endif
        data-nav-id="{{ $item['id'] }}"
        data-nav-icon="{{ $item['icon'] }}"
      >
        <span data-nav-label>{{ $item['label'] }}</span>
      </a>
    @endforeach
  </nav>
  <div class="sidebar-bottom">
    <div class="sidebar-tip">
      <span class="sun" data-icon="clarity"></span>
      <strong>A little clarity, every day.</strong>
      <p>Everything your team needs to keep care moving.</p>
    </div>
    <button class="profile" id="profileButton" type="button">
      @if ($userAvatarUrl ?? null)
        <img class="avatar pale avatar-image" src="{{ $userAvatarUrl }}" alt="">
      @else
        <span class="avatar pale">{{ $userInitials }}</span>
      @endif
      <span>{{ $user->name }}<small>{{ $roleLabel }}</small></span>
      <span class="profile-more">···</span>
    </button>
  </div>
</aside>
