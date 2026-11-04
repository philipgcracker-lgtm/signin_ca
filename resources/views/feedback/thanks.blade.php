@extends('checkin.layout')

@section('title', 'Thank you')

@section('content')
<div class="card">
  <h1>Thank you</h1>
  <p class="sub">Your feedback on {{ $event->shortAddress() }} has been sent.</p>

  @if ($reviewUrl)
    <div class="agent" style="margin-top:16px">
      <div>
        <b>Did you meet {{ $agent->full_name }}?</b>
        Tell others about your experience.
      </div>
    </div>
    <a class="btn-link" href="{{ $reviewUrl }}" target="_blank" rel="noopener noreferrer">Write a Google review</a>
  @endif
</div>
@endsection
