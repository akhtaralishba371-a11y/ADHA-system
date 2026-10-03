@extends('adhd.layouts.app')

@section('title', 'How it works | Jelly')
@section('meta_description', 'Understand the Jelly assessment booking process.')
@section('body_class', 'content-page')

@section('content')

  @include('adhd.partials.header', ['currentPage' => 'how-it-works'])

  <main id="main-content" class="content-main process-page">
    <header class="content-hero">
      <span class="eyebrow">What to expect</span>
      <h1>Clear steps.<br>No surprises.</h1>
      <p>From choosing an appointment to receiving your report, we'll explain what happens and what you need to do.</p>
      <a class="inline-cta" href="{{ route('adhd.home') }}#pathway">Start your booking <span>→</span></a>
    </header>

    <section class="process-list" aria-label="Assessment process">
      <article>
        <span>01</span>
        <div><small>Choose your pathway</small><h2>Find the right appointment</h2><p>Tell us whether you're booking for an adult or a child, then choose ADHD, autism or combined assessment.</p></div>
        <em>About 2 minutes</em>
      </article>
      <article>
        <span>02</span>
        <div><small>Your preferences</small><h2>Choose how, who and when</h2><p>Select online or in-person care, review clinician profiles and find a date that works for you.</p></div>
        <em>Live availability</em>
      </article>
      <article>
        <span>03</span>
        <div><small>Before the assessment</small><h2>Complete your questionnaires</h2><p>We'll send secure forms for the patient and, where relevant, someone who knows them well.</p></div>
        <em>Complete at home</em>
      </article>
      <article>
        <span>04</span>
        <div><small>Your appointment</small><h2>A conversation, not a test</h2><p>Your clinician will explore history, strengths, challenges and day-to-day experiences in a calm structured consultation.</p></div>
        <em>Usually 75–120 min</em>
      </article>
      <article>
        <span>05</span>
        <div><small>Afterwards</small><h2>Clear outcomes and next steps</h2><p>You'll receive an explanation of the outcome, a written report and personalised recommendations for ongoing support.</p></div>
        <em>Written report</em>
      </article>
    </section>

    <aside class="process-help">
      <div>
        <span class="eyebrow">Still deciding?</span>
        <h2>Talk it through with us.</h2>
        <p>Our care team can explain the options before you commit to a booking.</p>
      </div>
      <a href="tel:02039661551">Call 0203 966 1551 <span>→</span></a>
    </aside>
  </main>

  @include('adhd.partials.footer')

@endsection
