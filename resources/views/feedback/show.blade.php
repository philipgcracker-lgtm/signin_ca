@extends('checkin.layout')

@section('title', 'How was the open house?')

@section('content')
<div class="card">
  <h1>How did you like {{ $event->shortAddress() }}?</h1>
  <p class="sub">Two quick questions. It takes about 20 seconds.</p>

  @if ($errors->any())
    <div class="err"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="post" action="{{ $action }}">
    @csrf

    <label>Your rating (1 = not for me, 5 = loved it)</label>
    <div style="display:flex;gap:8px">
      @foreach ([1, 2, 3, 4, 5] as $n)
        <label style="flex:1;margin:0;text-align:center;font-weight:700">
          <input type="radio" name="score" value="{{ $n }}" style="width:100%;height:22px" @checked((string) old('score') === (string) $n) required>
          {{ $n }}
        </label>
      @endforeach
    </div>

    <label for="notes">Anything you'd like to add? <span style="font-weight:400;color:#6b7280">(optional)</span></label>
    <textarea id="notes" name="notes" rows="4" maxlength="2000" style="width:100%;font:inherit;padding:12px;border:1px solid #9ca3af;border-radius:8px">{{ old('notes') }}</textarea>
    <p class="purpose">Comments may be shared with the home's seller without your name.</p>

    <button type="submit">Send</button>
  </form>
</div>
@endsection
