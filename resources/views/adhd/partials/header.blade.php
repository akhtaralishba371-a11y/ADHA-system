<header class="site-header">
  <a class="brand" href="{{ route('adhd.home') }}" aria-label="Jelly home">
    <span class="brand-mark" aria-hidden="true">
      <svg viewBox="0 0 42 42"><path d="M21 4.5c-9.1 0-16.5 7.4-16.5 16.5S11.9 37.5 21 37.5 37.5 30.1 37.5 21 30.1 4.5 21 4.5Zm0 7.3a9.2 9.2 0 1 1 0 18.4 9.2 9.2 0 0 1 0-18.4Z"/><path d="M21 16.4a4.6 4.6 0 1 0 0 9.2 4.6 4.6 0 0 0 0-9.2Z"/></svg>
    </span>
    <span><strong>Jelly</strong><small>Specialist care, clearly arranged</small></span>
  </a>
  <nav class="header-nav" aria-label="Primary navigation">
    <a @if($currentPage === 'home') class="is-current" aria-current="page" @endif href="{{ route('adhd.home') }}#pathway">Book an assessment</a>
    <a @if($currentPage === 'clinicians') class="is-current" aria-current="page" @endif href="{{ route('adhd.clinicians') }}">Our clinicians</a>
    <a @if($currentPage === 'how-it-works') class="is-current" aria-current="page" @endif href="{{ route('adhd.how-it-works') }}">How it works</a>
  </nav>
  <div class="header-actions">
    <a class="quiet-link" href="tel:02039661551"><span>Need help?</span> 0203 966 1551</a>
    @if($currentPage === 'home')
    <button class="icon-button" id="saveExit" type="button" aria-label="Save and exit">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h9l3 3v13H6V4Zm3 0v6h6V4M9 15h6"/></svg>
      <span>Save &amp; exit</span>
    </button>
    @endif
  </div>
</header>
