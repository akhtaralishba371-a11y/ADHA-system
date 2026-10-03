@extends('adhd.layouts.app')

@section('title', 'Our clinicians | Jelly')
@section('meta_description', 'Meet our clinical team of psychiatrists and psychologists.')
@section('body_class', 'content-page')

@section('content')

  @include('adhd.partials.header', ['currentPage' => 'clinicians'])

  <main id="main-content" class="content-main">
    <header class="content-hero">
      <span class="eyebrow">People first</span>
      <h1>Specialist care,<br>with a human face.</h1>
      <p>Meet our team of psychiatrists and psychologists.</p>
      <a class="inline-cta" href="{{ route('adhd.home') }}#pathway">Book an assessment <span>&rarr;</span></a>
    </header>

    <section class="team-section" aria-labelledby="team-title">
      <div class="section-kicker">
        <span id="team-title">Clinical team</span>
        <p>Choose a clinician during booking, or ask us to find the soonest suitable appointment.</p>
      </div>
      <div class="team-grid">
        @forelse ($clinicians as $clinician)
          <article class="team-card">
            @if ($clinician->photo_url)
              <img src="{{ $clinician->photo_url }}" alt="{{ $clinician->display_name }}">
            @else
              <div class="team-photo-fallback" aria-hidden="true">{{ $clinician->initials }}</div>
            @endif
            <div>
              <span>{{ $clinician->specialty }}@if ($clinician->location) · {{ $clinician->location }}@endif</span>
              <h2>{{ $clinician->display_name }}</h2>
              <p>{{ $clinician->professional_title ?: 'Clinician' }}</p>
              @if ($clinician->years_experience)
                <ul><li>{{ $clinician->years_experience }} years' experience</li></ul>
              @endif
              <a href="{{ route('adhd.home', ['clinician' => $clinician->public_id]) }}">View availability &rarr;</a>
            </div>
          </article>
        @empty
          <p>Our clinician directory is being updated. Please contact our care team for availability.</p>
        @endforelse
      </div>
    </section>
  </main>

  @include('adhd.partials.footer')

@endsection
