@extends('adhd.layouts.app')

@section('title', 'Book an assessment | Jelly')
@section('meta_description', 'Book an ADHD or autism assessment with Jelly.')
@section('skip_href', '#pathway')
@section('skip_text', 'Skip to booking')

@section('content')

  <div class="site-loader" id="siteLoader" aria-label="Loading Jelly">
    <div class="loader-symbol"><i></i><i></i><i></i></div>
    <strong>Jelly</strong>
    <span>Preparing your booking</span>
  </div>

  @include('adhd.partials.header', ['currentPage' => 'home'])

  <main class="pathway-screen" id="pathway" hidden>
    <div class="pathway-copy">
      <span class="eyebrow">Private ADHD &amp; autism care</span>
      <h1>A little clarity<br>starts here.</h1>
      <p>Choose who you're booking for. We'll take it one calm, clear step at a time.</p>
    </div>
    <div class="pathway-cards" aria-label="Choose a booking pathway">
      <button class="pathway-card adult-pathway" type="button" data-pathway="adult">
        <span class="pathway-card-top"><small>Adult care</small><em>18 and over</em></span>
        <span class="pathway-image" aria-hidden="true">
    <img src="{{ asset('assets/ADHD/images/imagegen/booking-pathway-adult.png') }}" alt="" width="480" height="640" decoding="async">
</span>
        <span class="pathway-content"><strong>Adults</strong><span>For you or another adult seeking specialist support.</span></span>
        <span class="pathway-action"><b>Explore adult care</b><i>→</i></span>
      </button>
      <button class="pathway-card child-pathway" type="button" data-pathway="child">
        <span class="pathway-card-top"><small>Child &amp; family care</small><em>Under 18</em></span>
<span class="pathway-image" aria-hidden="true">
    <img src="{{ asset('assets/ADHD/images/imagegen/booking-pathway-child-parent.png') }}" alt="" width="480" height="640" decoding="async">
</span>
        <span class="pathway-content"><strong>Children &amp;<br>young people</strong><span>Support for your child, with you alongside them.</span></span>
        <span class="pathway-action"><b>Explore child care</b><i>→</i></span>
      </button>
    </div>
    <div class="pathway-footer" id="support">
      <p><span></span> Online across the UK <i>·</i> London <i>·</i> Manchester</p>
      <a href="tel:02039661551">Not sure which to choose? <strong>Our care team can help →</strong></a>
    </div>
  </main>

  <div class="booking-shell" id="bookingShell" hidden>
    <aside class="journey-rail" aria-label="Booking progress">
      <div class="rail-intro">
        <button class="switch-pathway" id="switchPathway" type="button">← Change pathway</button>
        <span class="eyebrow">Private booking</span>
        <h1>Book your assessment</h1>
        <p>Choose what feels right for you. You can go back and change any answer.</p>
      </div>

      <ol class="step-list" id="stepList">
        <li class="is-active" data-step="0"><span>1</span><div><strong>Appointment</strong><small>Choose a service</small></div></li>
        <li data-step="1"><span>2</span><div><strong>How &amp; where</strong><small>Online or in clinic</small></div></li>
        <li data-step="2"><span>3</span><div><strong>Clinician</strong><small>Pick your preference</small></div></li>
        <li data-step="3"><span>4</span><div><strong>Date &amp; time</strong><small>Find a suitable slot</small></div></li>
        <li data-step="4"><span>5</span><div><strong>Your details</strong><small>Patient information</small></div></li>
        <li data-step="5"><span>6</span><div><strong>Confirm</strong><small>Consent and review</small></div></li>
      </ol>

      <div class="privacy-note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v10H5z"/></svg>
        <p><strong>Your information stays private.</strong> We only ask for what is needed to arrange your care.</p>
      </div>
    </aside>

    <main id="booking-main" class="booking-main">
      <div class="mobile-progress" aria-hidden="true"><span id="mobileProgressText">Step 1 of 6</span><div><i id="mobileProgressBar"></i></div></div>

      {{-- Step 0: Service --}}
      <section class="booking-step is-visible" data-panel="0">
        <div class="step-heading">
          <span class="eyebrow">Start with the appointment</span>
          <h2>What would you like help with?</h2>
          <p>Choose one option. We'll only show appointments relevant to you.</p>
        </div>
        <div class="choice-grid service-grid" id="serviceChoices">
          <div class="empty" id="serviceChoicesLoading"><p>Loading appointment options…</p></div>
        </div>
        <details class="help-card"><summary>I'm not sure which appointment I need</summary><p>Call our care team on <a href="tel:02039661551">0203 966 1551</a>. They can help you choose without any obligation to book.</p></details>
      </section>

      {{-- Step 1: Format --}}
      <section class="booking-step" data-panel="1" hidden>
        <div class="step-heading"><span class="eyebrow">How &amp; where</span><h2>How would you like to meet?</h2><p>Online appointments are available across the United Kingdom.</p></div>
        <div class="choice-grid format-grid">
          <button class="choice-card format-card" type="button" data-value="Online video appointment">
            <span class="format-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg></span>
            <span class="choice-copy"><strong>Online video appointment</strong><small>Join securely from a quiet, private place anywhere in the UK.</small><em>Usually the earliest availability</em></span><span class="choice-tick">✓</span>
          </button>
          <button class="choice-card format-card" type="button" data-value="In-person appointment">
            <span class="format-icon"><svg viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
            <span class="choice-copy"><strong>In-person appointment</strong><small>Visit one of our calm, private clinic locations.</small><em>London or Manchester</em></span><span class="choice-tick">✓</span>
          </button>
        </div>
        <div class="location-panel" id="locationPanel" hidden>
          <h3>Choose a clinic</h3>
          <div class="location-grid">
            <button class="location-card" type="button" data-value="Marylebone, London"><span>London</span><strong>Marylebone</strong><small>85 Wimpole Street · W1G 9RJ</small><i>View on map ↗</i></button>
            <button class="location-card" type="button" data-value="Belgravia, London"><span>London</span><strong>Belgravia</strong><small>9 Eccleston Street · SW1W 9LX</small><i>View on map ↗</i></button>
            <button class="location-card" type="button" data-value="Didsbury, Manchester"><span>Manchester</span><strong>Didsbury</strong><small>599 Wilmslow Road · M20 3QD</small><i>View on map ↗</i></button>
          </div>
        </div>
      </section>

      {{-- Step 2: Clinician --}}
      <section class="booking-step" data-panel="2" hidden>
        <div class="step-heading"><span class="eyebrow">Your clinician</span><h2>Choose what matters most</h2><p>Select a clinician, or let us find the soonest suitable appointment.</p></div>
        <button class="soonest-card choice-card" type="button" data-value="First available clinician">
          <span class="soonest-badge">Soonest</span><span class="choice-copy"><strong>Show me the first available appointment</strong><small>We'll match you with an experienced clinician for your selected service.</small></span><span class="arrow">→</span>
        </button>
        <div class="section-divider"><span>or choose a clinician</span></div>
        <div class="clinician-grid" id="clinicians">
          @forelse ($clinicians as $clinician)
            <article class="clinician-card" data-clinician-ref="{{ $clinician->public_id }}" data-value="{{ $clinician->display_name }}" data-role="{{ $clinician->professional_title ?: 'Clinician' }}" data-specialty="{{ $clinician->specialty }}" data-bio="{{ $clinician->bio ?: 'A member of our specialist care team.' }}" data-experience="{{ $clinician->years_experience ? $clinician->years_experience.' years' : 'Experience available on request' }}" data-location="{{ $clinician->location ?: 'Online' }}" data-initials="{{ $clinician->initials }}">
              <div class="portrait">
                @if ($clinician->photo_url)
                  <img src="{{ $clinician->photo_url }}" alt="{{ $clinician->display_name }}">
                @else
                  <span aria-hidden="true">{{ $clinician->initials }}</span>
                @endif
              </div>
              <div class="clinician-info"><h3>{{ $clinician->display_name }}</h3><p>{{ $clinician->professional_title ?: 'Clinician' }} · {{ $clinician->specialty }}</p><div class="tags">@if ($clinician->years_experience)<span>{{ $clinician->years_experience }} years</span>@endif<span>{{ $clinician->location ?: 'Online' }}</span></div></div>
              <div class="clinician-actions"><button class="text-button profile-button" type="button">View profile</button><button class="select-clinician" type="button">Choose {{ preg_replace('/^Dr\.?\s+/i', 'Dr ', $clinician->display_name) }}</button></div>
            </article>
          @empty
            <p>No specific clinician is currently available. Choose the first available appointment.</p>
          @endforelse
          @if (false)
          <article class="clinician-card" data-value="Dr. M. Danial Arif" data-role="Psychologist at Digital Genei Hospital" data-specialty="Psychology" data-gmc="Registration details to be added" data-bio="Dr. M. Danial Arif is a Psychologist." data-style="To be added" data-languages="To be added">
            <div class="portrait"><img src="{{ asset('assets/ADHD/images/doctors/dr-m-danial-arif-doctor.png') }}" alt="Dr. M. Danial Arif"></div>
            <div class="clinician-info"><h3>Dr. M. Danial Arif</h3><p>Psychologist at Digital Genei Hospital</p></div>
            <div class="clinician-actions"><button class="text-button profile-button" type="button">View profile</button><button class="select-clinician" type="button">Choose Dr. Arif</button></div>
          </article>
          <article class="clinician-card" data-value="Dr Oliver Bennett" data-role="Consultant Psychiatrist" data-specialty="Adult ADHD, mood and anxiety" data-gmc="Demo profile · GMC details pending" data-bio="Dr Bennett takes a thoughtful, structured approach to adult ADHD assessment. His consultations focus on the whole picture — attention, work, relationships, sleep and emotional wellbeing — with time for questions throughout." data-style="Calm, structured and direct" data-languages="English">
            <div class="portrait portrait-one"><img src="{{ asset('assets/ADHD/images/doctors/dr-oliver-bennett.png') }}" alt="Fictional demo portrait for Dr Oliver Bennett"></div>
            <div class="clinician-info"><span class="availability">Next available · Sat 5 Sep</span><h3>Dr Oliver Bennett</h3><p>Consultant Psychiatrist · Adult ADHD</p><div class="tags"><span>15+ years</span><span>Online</span><span>Manchester</span></div></div>
            <div class="clinician-actions"><button class="text-button profile-button" type="button">View profile</button><button class="select-clinician" type="button">Choose Dr Bennett</button></div>
          </article>
          <article class="clinician-card" data-value="Dr Amelia Clarke" data-role="Consultant Psychiatrist" data-specialty="ADHD and autism across adulthood" data-gmc="Demo profile · GMC details pending" data-bio="Dr Clarke works with adults exploring ADHD, autism and overlapping presentations. She is known for explaining clinical language clearly and creating a comfortable, collaborative assessment experience." data-style="Warm, curious and collaborative" data-languages="English">
            <div class="portrait portrait-two"><img src="{{ asset('assets/ADHD/images/doctors/dr-amelia-clarke.png') }}" alt="Fictional demo portrait for Dr Amelia Clarke"></div>
            <div class="clinician-info"><span class="availability">Next available · Mon 7 Sep</span><h3>Dr Amelia Clarke</h3><p>Consultant Psychiatrist · ADHD &amp; Autism</p><div class="tags"><span>12+ years</span><span>Online</span><span>London</span></div></div>
            <div class="clinician-actions"><button class="text-button profile-button" type="button">View profile</button><button class="select-clinician" type="button">Choose Dr Clarke</button></div>
          </article>
          <article class="clinician-card" data-value="Dr Sophie Williams" data-role="Consultant Psychiatrist" data-specialty="Adult neurodevelopment and complex presentations" data-gmc="Demo profile · GMC details pending" data-bio="Dr Williams supports adults with complex or previously unclear presentations. Her assessments bring together developmental history and present-day experience to produce practical, individual recommendations." data-style="Measured, thorough and reassuring" data-languages="English and Gujarati">
            <div class="portrait portrait-three"><img src="{{ asset('assets/ADHD/images/doctors/dr-sophie-williams.png') }}" alt="Fictional demo portrait for Dr Sophie Williams"></div>
            <div class="clinician-info"><span class="availability">Next available · Wed 9 Sep</span><h3>Dr Sophie Williams</h3><p>Consultant Psychiatrist · Adult neurodevelopment</p><div class="tags"><span>18+ years</span><span>Online</span><span>London</span></div></div>
            <div class="clinician-actions"><button class="text-button profile-button" type="button">View profile</button><button class="select-clinician" type="button">Choose Dr Williams</button></div>
          </article>
          @endif
        </div>
      </section>

      {{-- Step 3: Date & Time --}}
      <section class="booking-step" data-panel="3" hidden>
        <div class="step-heading"><span class="eyebrow">Date &amp; time</span><h2>Choose an appointment</h2><p>Times shown are in UK local time. We will confirm your booking by email.</p></div>
        <div class="date-layout">
          <div class="calendar-card">
            <div class="calendar-head">
              <button type="button" id="calendarPrev" aria-label="Previous month">←</button>
              <strong id="calendarMonthLabel">September 2026</strong>
              <button type="button" id="calendarNext" aria-label="Next month">→</button>
            </div>
            <div class="calendar-week"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
            <div class="calendar-days" id="calendarDays"></div>
            <div class="calendar-key"><span><i></i>Appointments available</span><span><i></i>Selected</span></div>
          </div>
          <div class="time-panel">
            <div class="selected-date"><span>Selected date</span><strong id="selectedDateLabel">Saturday, 5 September</strong></div>
            <div class="time-group"><h3>Morning</h3><div></div></div>
            <div class="time-group"><h3>Afternoon</h3><div></div></div>
            <p class="form-note" id="slotsEmptyNote" hidden>Loading available times…</p>
            <p class="slot-note"><span>○</span> Availability is checked again when you send your booking request.</p>
          </div>
        </div>
      </section>

      {{-- Step 4: Details --}}
      <section class="booking-step" data-panel="4" hidden>
        <div class="step-heading"><span class="eyebrow">Your details</span><h2>Who is this appointment for?</h2><p>We'll use these details for the booking confirmation and pre-assessment forms.</p></div>
        <form id="detailsForm" novalidate>
          <fieldset class="booking-for"><legend>Who are you booking for?</legend><label><input type="radio" name="bookingFor" value="Myself" checked><span>Myself</span></label><label><input type="radio" name="bookingFor" value="A child or dependant"><span>A child or dependant</span></label><label><input type="radio" name="bookingFor" value="Someone else"><span>Someone else</span></label></fieldset>
          <div class="form-section"><h3>Patient information</h3><div class="form-grid">
            <label><span>First name</span><input name="firstName" autocomplete="given-name" required></label>
            <label><span>Last name</span><input name="lastName" autocomplete="family-name" required></label>
            <label class="span-two"><span>Email address</span><input type="email" name="email" autocomplete="email" required><small>We'll send the confirmation and next steps here.</small></label>
            <label><span>Mobile number</span><input type="tel" name="phone" autocomplete="tel" required></label>
            <label><span>Date of birth</span><input type="date" name="dob" autocomplete="bday" required></label>
          </div></div>
          <div class="form-section"><h3>Home address</h3><div class="form-grid">
            <label class="span-two"><span>Address</span><input name="address" autocomplete="street-address" required></label>
            <label><span>Town or city</span><input name="city" autocomplete="address-level2" required></label>
            <label><span>Postcode</span><input name="postcode" autocomplete="postal-code" required></label>
          </div></div>
        </form>
      </section>

      {{-- Step 5: Review --}}
      <section class="booking-step" data-panel="5" hidden>
        <div class="step-heading"><span class="eyebrow">Review and confirm</span><h2>Check everything looks right</h2><p>You can change any section before sending your booking request.</p></div>
        <div class="review-layout">
          <div class="review-column">
            <section class="review-card"><div class="review-card-head"><h3>Appointment</h3><button type="button" data-edit="0">Change</button></div><dl id="appointmentReview"></dl></section>
            <section class="review-card"><div class="review-card-head"><h3>Patient</h3><button type="button" data-edit="4">Change</button></div><dl id="patientReview"></dl></section>
            <section class="consent-card"><h3>Before you continue</h3>
              <label><input type="checkbox" name="ukPresence"><span><strong>Appointment location</strong>I confirm the patient will be in the United Kingdom or Channel Islands at the time of the appointment.</span></label>
              <label><input type="checkbox" name="questionnaires"><span><strong>Pre-assessment questionnaires</strong>I understand the required questionnaires must be completed before the assessment.</span></label>
              <label><input type="checkbox" name="emergency"><span><strong>Urgent or emergency care</strong>I confirm this booking is not for a psychiatric emergency or immediate risk of harm.</span></label>
            </section>
          </div>
          <aside class="payment-summary">
            <span class="eyebrow">Booking request</span><div class="price-row"><strong id="summaryPrice">£1,095</strong><small>price shown before confirmation</small></div>
            <div class="summary-line"><span>Appointment</span><strong id="summaryService">ADHD assessment</strong></div>
            <div class="summary-line"><span>Duration</span><strong id="summaryDuration">90 minutes</strong></div>
            <div class="summary-line"><span>Next step</span><strong>Booking confirmation email</strong></div>
            <p>By continuing, you agree to our <a href="#">terms</a>, <a href="#">privacy policy</a> and <a href="#">cancellation policy</a>.</p>
            <p class="form-error" id="bookingSubmitError" hidden role="alert"></p>
            <button class="pay-button" id="payButton" type="button" disabled><span>Send booking request</span><svg viewBox="0 0 24 24"><path d="m5 12 14 0m-5-5 5 5-5 5"/></svg></button>
            <div class="secure-row"><span>SSL secured</span><span>Encrypted details</span></div>
          </aside>
        </div>
      </section>

      {{-- Step 6: Success --}}
      <section class="booking-step success-step" data-panel="6" hidden>
        <div class="success-mark">✓</div>
        <span class="eyebrow">Booking complete</span>
        <h2>Your appointment is confirmed</h2>
        <p>A confirmation and your next steps have been sent to <strong id="successEmail"></strong>.</p>
        <div class="success-ticket">
          <div><span>Date &amp; time</span><strong id="successDate"></strong></div>
          <div><span>Clinician</span><strong id="successClinician"></strong></div>
          <div><span>Reference</span><strong id="successReference">—</strong></div>
        </div>
        <div class="next-steps">
          <h3>What happens next</h3>
          <ol>
            <li><span>1</span><p><strong>Check your inbox</strong>Your booking receipt and appointment details are on their way.</p></li>
            <li><span>2</span><p><strong>Complete your questionnaires</strong>We'll email secure links for the forms required before your assessment.</p></li>
            <li><span>3</span><p><strong>We'll remind you</strong>You'll receive a reminder before the appointment.</p></li>
          </ol>
        </div>
        <button class="secondary-button" id="restartButton" type="button">Make another booking</button>
      </section>

      <div class="step-actions" id="stepActions">
        <button class="back-button" id="backButton" type="button" hidden><span>←</span> Back</button>
        <div class="action-right"><p class="selection-hint" id="selectionHint" role="status">Choose an appointment type to continue</p><button class="continue-button" id="continueButton" type="button" disabled>Continue <span>→</span></button></div>
      </div>
    </main>
  </div>

  <dialog id="profileDialog" class="profile-dialog">
    <button class="dialog-close" type="button" aria-label="Close profile">×</button>
    <div class="dialog-profile-head"><img id="dialogImage" src="" alt="" hidden><span id="dialogInitials" class="dialog-photo-fallback" hidden></span><div><span class="eyebrow">Clinician profile</span><h2 id="dialogName">Clinician</h2><p id="dialogRole"></p></div></div>
    <p class="dialog-bio" id="dialogBio"></p>
    <div class="dialog-facts"><span><small>Clinical focus</small><strong id="dialogSpecialty"></strong></span><span><small>Experience</small><strong id="dialogExperience"></strong></span><span><small>Appointments</small><strong id="dialogLocation"></strong></span></div>
  </dialog>

  <div class="toast" id="toast" role="status" aria-live="polite">Your progress has been saved on this device.</div>

@endsection

@push('scripts')
  <script>window.bookingConfig = {storeUrl: @json(route('bookings.store')), optionsUrl: @json(route('bookings.options')), availabilityUrl: @json(route('availability.index')), availabilityDatesUrl: @json(route('availability.dates')), csrfToken: @json(csrf_token())};</script>
 <script src="{{ route('adhd.booking.scripts') }}"></script>
@endpush
