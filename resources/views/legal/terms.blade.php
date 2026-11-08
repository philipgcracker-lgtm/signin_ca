@extends('legal.layout')

@section('title', 'Terms of service')

@php
    $operator = config('scanin.operator_name');
    $email = config('scanin.contact_email');
@endphp

@section('content')
<h1>Terms of service</h1>
<p class="upd">Last updated: November 7, 2026. These terms are for agents who use ScanIn. If you only checked in at an open house, see the <a href="{{ route('privacy') }}">Privacy policy</a>.</p>

<h2>1. The service</h2>
<p>ScanIn lets a real estate agent set up an open house, show visitors a check-in page through a QR code, text visitors who agree to it a link to the agent's own listing page, and see who came. {{ $operator }} runs ScanIn. By creating an account or using ScanIn you agree to these terms.</p>

<h2>2. Your account</h2>
<ul>
  <li>ScanIn is for real estate agents working in Ontario. New accounts are approved by us before they can create open houses.</li>
  <li>Your name, brokerage and email are shown to visitors on your check-in pages and the privacy page linked from them, so they know who to contact about their information.</li>
  <li>Give accurate information and keep your password private. You are responsible for what happens under your account.</li>
  <li>We may suspend or close an account that breaks these terms or puts visitors or the service at risk. When an account is suspended, its check-in pages and texts are switched off.</li>
</ul>

<h2>3. What you must do</h2>
<ul>
  <li><b>Use visitor information lawfully.</b> You are responsible for the visitors' information you collect through ScanIn. Use it only for what the visitor agreed to, and only for as long as you need it.</li>
  <li><b>Respect opt-outs.</b> ScanIn stops texts to anyone who replies STOP. Do not try to contact those people another way in connection with the open house texts, and do not ask ScanIn to override an opt-out.</li>
  <li><b>No unsolicited marketing.</b> Do not use visitor details for messages they did not agree to, and do not use ScanIn to message people who have not checked in.</li>
  <li><b>Use your own content.</b> Enter your own listing details and link to your own listing page. Do not upload, copy or scrape MLS data or other people's listing content without the right to use it.</li>
  <li><b>Follow your rules.</b> You are responsible for meeting the rules that apply to you as a registered real estate professional and to your brokerage, including advertising and privacy rules.</li>
  <li><b>Do not misuse the service.</b> No attacks, probing, scraping, reselling, or putting false or unlawful content into check-in pages.</li>
</ul>

<h2>4. Text messages</h2>
<p>Texts are sent through a third-party provider. We do not guarantee that any text is delivered, because delivery depends on carriers and the visitor's phone. Message and data rates may apply to visitors. We may block or stop a message that appears unlawful or abusive.</p>

<h2>5. Your data</h2>
<p>You keep ownership of the information you enter. You are the party responsible for visitor information; we handle it for you as described in the <a href="{{ route('privacy') }}">Privacy policy</a>. You can export your visitor list at any time. If a visitor asks to be deleted, we will erase their personal details and may tell you that we did.</p>

<h2>6. Price</h2>
<p>ScanIn is currently offered in a pilot. We may introduce fees. We will tell you by email before any charge starts, and you can stop using ScanIn instead.</p>

<h2>7. No warranty, and limits on liability</h2>
<p>ScanIn is provided as it is. We work to keep it available and accurate but do not promise it will be uninterrupted or error-free, so please keep a paper sign-in sheet as a backup at open houses. To the extent the law allows, we are not liable for lost business or indirect losses arising from your use of ScanIn.</p>

<h2>8. Ending your account</h2>
<p>You may stop using ScanIn at any time{{ '' }}@if (filled($email)) and ask us to close your account at <a href="mailto:{{ $email }}">{{ $email }}</a>@endif. Closing an account does not remove the consent records we keep to show texts were lawful.</p>

<h2>9. Changes and governing law</h2>
<p>We may update these terms and will post the new version here with a new date; for material changes we will email you. These terms are governed by the laws of Ontario and the federal laws of Canada that apply there.</p>

<h2>10. Contact</h2>
<p>{{ $operator }}@if (filled($email)), <a href="mailto:{{ $email }}">{{ $email }}</a>@endif.</p>
@endsection
