@extends('checkin.layout')

@section('title', "You're checked in")

@section('content')
<div class="card">
  <h1>You're checked in</h1>
  <p class="sub">{{ $event->shortAddress() }}</p>
  <p>Thanks for stopping by. We're texting you the listing details now. If the text doesn't arrive in a minute, use the button below.</p>
  <a class="btn-link" href="{{ $event->listing_url }}">View the listing</a>
</div>
@endsection
