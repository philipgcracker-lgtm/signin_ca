<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Door sign: {{ $event->shortAddress() }}</title>
<style>
@page { size: letter portrait; margin: 0.5in; }
*{box-sizing:border-box}
body{margin:0;background:#f3f4f6;color:#111827;font:16px/1.4 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.bar{max-width:8.5in;margin:0 auto;padding:12px 16px;display:flex;gap:12px;align-items:center;justify-content:space-between}
.bar a{color:#374151}
.bar button{padding:10px 16px;border:0;border-radius:8px;background:#111827;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.sheet{width:8.5in;max-width:100%;margin:0 auto;background:#fff;padding:0.5in;text-align:center}
.kicker{font-size:22pt;font-weight:800;letter-spacing:.04em;text-transform:uppercase;margin:0}
.addr{font-size:30pt;font-weight:800;margin:6px 0 0;line-height:1.1}
.when{font-size:16pt;color:#4b5563;margin:8px 0 0}
.qr{margin:18px auto 6px;width:5.4in;max-width:100%}
.qr svg{width:100%;height:auto;display:block}
.cta{font-size:26pt;font-weight:800;margin:6px 0 4px;line-height:1.15}
.sub{font-size:15pt;color:#374151;margin:0}
.agent{margin-top:20px;padding-top:14px;border-top:2px solid #111827;font-size:16pt}
.agent b{font-size:19pt}
.url{margin-top:12px;font-size:12pt;color:#4b5563;word-break:break-all}
.foot{margin-top:8px;font-size:10pt;color:#9ca3af}
@media print{
  body{background:#fff}
  .bar{display:none}
  .sheet{width:auto;padding:0}
}
</style>
</head>
<body>
<div class="bar">
  <a href="{{ route('events.show', $event->id) }}">&larr; Back to {{ $event->shortAddress() }}</a>
  <button type="button" onclick="window.print()">Print</button>
</div>
<div class="sheet">
  <p class="kicker">Open House</p>
  <p class="addr">{{ $event->address_street }}</p>
  <p class="when">{{ $event->displayStart()->format('l, F j') }} &middot; {{ $event->displayStart()->format('g:i A') }}&ndash;{{ $event->displayEnd()->format('g:i A') }}</p>

  <div class="qr">{!! $qr !!}</div>

  <p class="cta">Scan to check in</p>
  <p class="sub">Get the listing details on your phone.</p>

  <div class="agent">
    <b>{{ $agent->full_name }}</b><br>
    {{ $agent->brokerage_name }}
    @if ($agent->phone)<br>{{ $agent->phone }}@endif
  </div>
  <p class="url">{{ $url }}</p>
  <p class="foot">Powered by ScanIn</p>
</div>
</body>
</html>
