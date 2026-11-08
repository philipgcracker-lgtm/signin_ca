@extends('checkin.layout')

@section('title', 'Check in: '.$event->shortAddress())

@section('content')
<div class="card">
  @if (filled($event->primary_image_url))
    <img class="photo" src="{{ $event->primary_image_url }}" alt="{{ $event->address_street }}" loading="lazy">
  @endif

  <h1>{{ $event->address_street }}</h1>
  <p class="sub">{{ $event->city }}, {{ $event->province }}@if (filled($event->postal_code)) {{ $event->postal_code }}@endif
    &middot; {{ $event->displayStart()->format('D, M j') }}, {{ $event->displayStart()->format('g:i') }}&ndash;{{ $event->displayEnd()->format('g:i A') }}</p>

  @if ($event->price || $event->bedrooms || $event->bathrooms)
    <div class="facts">
      @if ($event->price)<span>${{ number_format((float) $event->price) }}</span>@endif
      @if ($event->bedrooms)<span>{{ $event->bedrooms }} bed</span>@endif
      @if ($event->bathrooms)<span>{{ rtrim(rtrim(number_format((float) $event->bathrooms, 1), '0'), '.') }} bath</span>@endif
    </div>
  @endif

  @if (filled($event->blurb))
    <p class="blurb">{{ $event->blurb }}</p>
  @endif

  <div class="agent">
    @if (filled($agent->headshot_url))
      <img src="{{ $agent->headshot_url }}" alt="" loading="lazy">
    @endif
    <div>
      <b>{{ $agent->full_name }}</b>
      @if (filled($agent->brokerage_name))<span class="sub" style="margin:0">{{ $agent->brokerage_name }}</span>@endif
    </div>
  </div>

  @if ($errors->any())
    <div class="err" role="alert">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('checkin.store', $event) }}" novalidate>
    @csrf
    <input type="hidden" name="consent_text_id" value="{{ $consent->id }}">

    <div class="hp" aria-hidden="true">
      <label for="website">Leave this field empty</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required>

    <label for="phone">Mobile number</label>
    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" placeholder="613 555 0123" required>

    <div class="consent">
      <input type="checkbox" id="consent" name="consent" value="1" @checked(old('consent')) required>
      <label for="consent" style="margin:0;font-weight:400"><span>{{ $consentBody }}</span></label>
    </div>
    <p class="purpose">We use your details only to send you this listing and, if you agree above, ask for your feedback. <a href="{{ route('privacy', ['e' => $event->slug]) }}" target="_blank" rel="noopener" style="color:inherit">Read our privacy policy</a>.</p>

    <details>
      <summary>Tell us a little more (optional)</summary>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email">

      <label for="working_with_agent">Are you working with an agent?</label>
      <select id="working_with_agent" name="working_with_agent">
        <option value="">Prefer not to say</option>
        <option value="1" @selected(old('working_with_agent') === '1')>Yes</option>
        <option value="0" @selected(old('working_with_agent') === '0')>No</option>
      </select>

      <label for="buying_timeline">When are you looking to buy?</label>
      <select id="buying_timeline" name="buying_timeline">
        <option value="">Prefer not to say</option>
        <option value="immediate" @selected(old('buying_timeline') === 'immediate')>As soon as possible</option>
        <option value="1_3_months" @selected(old('buying_timeline') === '1_3_months')>In 1 to 3 months</option>
        <option value="3_6_months" @selected(old('buying_timeline') === '3_6_months')>In 3 to 6 months</option>
        <option value="browsing" @selected(old('buying_timeline') === 'browsing')>Just browsing</option>
      </select>

      <label for="pre_approved">Are you pre-approved for a mortgage?</label>
      <select id="pre_approved" name="pre_approved">
        <option value="">Prefer not to say</option>
        <option value="1" @selected(old('pre_approved') === '1')>Yes</option>
        <option value="0" @selected(old('pre_approved') === '0')>No</option>
      </select>
    </details>

    <button type="submit">Check in</button>
  </form>
</div>
@endsection
