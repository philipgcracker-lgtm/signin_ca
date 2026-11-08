@extends('checkin.layout')

@section('title', 'Please try again')

@section('content')
<div class="card">
  <h1>That didn't go through</h1>
  <p class="sub">{{ $event->shortAddress() }}</p>
  <p>{{ $reason }} Your check-in was not saved. Please try again, or use the button below to see the listing.</p>
  <a class="btn-link" href="{{ route('checkin.show', $event) }}">Back to the form</a>
  <a class="btn-link" href="{{ $event->listing_url }}" style="margin-top:.75rem">View the listing</a>
</div>
@endsection
