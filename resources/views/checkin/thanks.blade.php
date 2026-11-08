@extends('checkin.layout')

@section('title', "You're checked in")

@section('content')
<div class="card">
  <h1>You're checked in</h1>
  <p class="sub">{{ $event->shortAddress() }}</p>
  <p>Thanks for stopping by. Use the button below for the listing details. If you agreed to a text, it should arrive within a minute.</p>
  <a class="btn-link" href="{{ $event->listing_url }}">View the listing</a>
</div>
@endsection
