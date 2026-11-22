@extends('legal.layout')

@section('title', 'Free printable open house sign-in sheet (Canada)')
@section('description', 'Print a free open house sign-in sheet for Canadian agents: name, mobile, email, working with an agent, and an OK-to-text tick box with consent wording.')

@push('head')
<link rel="canonical" href="{{ route('sheet') }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('sheet') }}">
<meta property="og:title" content="Free printable open house sign-in sheet (Canada)">
<meta property="og:description" content="A one-page open house sign-in sheet with an OK-to-text tick box and the consent wording printed on it.">
<meta name="twitter:card" content="summary">
<style>
.sheet{margin:24px 0}
.sheet h2.s{font-size:1.3rem;margin:0 0 12px}
.fill{display:flex;gap:24px;flex-wrap:wrap;margin:0 0 16px;font-size:.95rem}
.fill span{flex:1 1 220px;border-bottom:1px solid #1f2623;padding-bottom:2px;min-height:1.6em}
table.grid{border-collapse:collapse;width:100%;font-size:.9rem}
table.grid th,table.grid td{border:1px solid #1f2623;padding:0 6px;text-align:left}
table.grid th{background:#e3eadf;height:2em;font-weight:600}
table.grid td{height:2.6em}
table.grid td.c,table.grid th.c{width:12%;text-align:center}
.consent{margin:14px 0 0;font-size:.85rem}
.cta{background:#e3eadf;border-radius:10px;padding:14px 16px;margin:24px 0 0}
.cta a{font-weight:600}
@media print{
  @page{size:landscape;margin:12mm}
  body{background:#fff}
  .top,.foot,.intro,.cta,.howto{display:none}
  article{border:0;padding:0;background:#fff}
  .sheet{margin:0}
  table.grid td{height:3em}
}
</style>
@endpush

@section('content')
<div class="intro">
<h1>Free printable open house sign-in sheet</h1>
<p class="upd">For Canadian real estate agents. One page, no sign-up.</p>
<p>Use this sheet at the door of your next open house. It asks for the details agents actually follow up on, and it prints the consent wording right on the page so a guest who ticks "OK to text" has seen what they agreed to.</p>
</div>

<div class="howto">
<h2>How to print it</h2>
<ul>
<li>Open your browser's print dialog (Ctrl+P, or Cmd+P on a Mac).</li>
<li>Choose landscape, and turn headers and footers off for a clean page.</li>
<li>Write the property address, date and your name on the lines before guests arrive.</li>
</ul>
</div>

<div class="sheet">
<h2 class="s">Open house sign-in</h2>
<div class="fill"><span>Property address:</span><span>Date:</span><span>Agent:</span></div>
<table class="grid">
<thead><tr><th>Name</th><th>Mobile number</th><th>Email (optional)</th><th class="c">Working with an agent?</th><th class="c">OK to text</th></tr></thead>
<tbody>
@for ($i = 0; $i < 14; $i++)
<tr><td></td><td></td><td></td><td class="c">Y &nbsp; N</td><td class="c">&#9633;</td></tr>
@endfor
</tbody>
</table>
<p class="consent">{{ $consent }}</p>
</div>

<div class="intro">
<h2>Why the consent line is on the sheet</h2>
<p>If you plan to text a visitor the listing link, you need their agreement before you send it. A tick box with the wording printed next to it gives you something specific to point to later. Only text guests who ticked the box.</p>
<p>This sheet is a template, not legal advice. Check the wording with your brokerage or a lawyer before you rely on it.</p>

<h2>Skip the paper</h2>
<p>ScanIn does the same job without the clipboard. Guests scan a QR code at the door and check in on their own phone, ScanIn texts them your listing link, and every guest goes into one list with their consent recorded. Paper guests can be added to the same list afterwards.</p>
<div class="cta"><a href="{{ url('/') }}">See how ScanIn works</a></div>
</div>
@endsection
