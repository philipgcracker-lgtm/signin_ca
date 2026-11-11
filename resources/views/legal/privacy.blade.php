@extends('legal.layout')

@section('title', 'Privacy policy')
@section('description', 'How ScanIn collects, uses and protects the information open house visitors share when they check in, and how to ask for it to be deleted.')

@php
    $operator = config('scanin.operator_name');
    $address = config('scanin.mailing_address');
    // Visitors are pointed to the agent who ran their open house (when they came from a
    // check-in page); otherwise to ScanIn's own address if one is set.
    $email = $agent?->email ?: config('scanin.contact_email');
    $who = $agent ? trim($agent->full_name.(filled($agent->brokerage_name) ? ', '.$agent->brokerage_name : '')) : null;
@endphp

@section('content')
<h1>Privacy policy</h1>
<p class="upd">Last updated: November 11, 2026</p>

<h2>1. Who we are</h2>
<p>ScanIn (scanin.ca) is software that real estate agents in Ontario use to run open houses. It is operated by {{ $operator }}@if (filled($address)), {{ $address }}@endif.
@if ($agent)
  For privacy questions or requests about your check-in, contact the agent who ran your open house: {{ $who }}, <a href="mailto:{{ $agent->email }}">{{ $agent->email }}</a>.
@elseif (filled($email))
  Privacy questions and requests: <a href="mailto:{{ $email }}">{{ $email }}</a>.
@else
  For privacy questions or requests, contact the agent whose open house you attended and they will pass it to us.
@endif
@if (filled(config('scanin.contact_email')) && $agent)
  To reach ScanIn itself, for example about how ScanIn handles your information: <a href="mailto:{{ config('scanin.contact_email') }}">{{ config('scanin.contact_email') }}</a>.
@endif
</p>

<h2>2. If you checked in at an open house</h2>

<h3>Who is responsible for your information</h3>
<p>The agent who ran the open house, and their brokerage, decide why your information is collected and how it is used. ScanIn handles it on their behalf to run the check-in and send the messages you agreed to.</p>

<h3>What we collect</h3>
<ul>
  <li><b>Required:</b> your full name, your mobile number, and your choice on the text-message consent box.</li>
  <li><b>Optional, only if you fill them in:</b> your email, whether you are working with an agent, when you are looking to buy, and whether you are pre-approved for a mortgage. You can leave these blank.</li>
  <li><b>Automatically:</b> the time of check-in, your IP address and browser details. These help prevent misuse of the form and are kept with your consent record.</li>
  <li><b>If you use our texts:</b> the text of any reply you send, and whether you opened the listing link we texted you.</li>
  <li><b>If you give feedback:</b> your 1 to 5 rating and any comments.</li>
</ul>

<h3>Why we collect it</h3>
<ul>
  <li>To tell the agent you visited.</li>
  <li>To text you the listing link, if you agreed, and, if you agreed, to ask for your feedback.</li>
  <li>The agent and ScanIn use your details only for these purposes and for the contact described in the consent wording you saw when you checked in.</li>
  <li>To prevent misuse of the check-in form and to keep a record of your consent.</li>
</ul>

<h3>Text messages</h3>
<p>If you tick the consent box, you receive a text from the agent through ScanIn with the listing link. Message and data rates may apply. Reply <b>STOP</b> at any time and no further messages are sent to your number, from any agent using ScanIn. The exact wording you agreed to is saved with your check-in.</p>

<h3>Who sees it</h3>
<ul>
  <li>The agent and brokerage who ran the open house.</li>
  <li>Service providers that help us run ScanIn: our web host, our text-message provider (Twilio), and our email provider.</li>
  <li>No one else. We do not sell personal information.</li>
</ul>

<h3>Where it is stored</h3>
<p>ScanIn's database and files are hosted with a Canadian hosting provider.</p>
<p>Our text-message provider, Twilio, is based in the United States. Your mobile number and the text of messages sent to or from you pass through Twilio and are stored by Twilio in the United States, where they may be accessible to US authorities under US law.</p>

<h3>How long we keep it</h3>
<p>Check-in information is erased automatically 24 months after the open house ends: your name, number, email, answers, any feedback comments and the text of your messages. What stays is the same as when you ask us to delete it (see "Your choices and rights" below), and the consent record is kept to show the texts were lawful. You can ask us to delete your information sooner at any time.</p>

<h3>Your choices and rights</h3>
<ul>
  <li><b>Stop texts:</b> reply STOP to any text from us.</li>
  <li><b>Delete your information:</b> reply DELETE to a text from us{{ '' }}@if (filled($email)), or email <a href="mailto:{{ $email }}">{{ $email }}</a>@endif. We erase your name, number, email, answers, any feedback comments and the text of your messages. What stays: a record that you gave and then withdrew consent (the dates, the wording shown, the agent, a reference number, and the IP address and browser used), which does not contain your name or number, kept so that we can show the texts were lawful and answer any complaint or claim. Your check-in time and any 1 to 5 feedback rating also stay, with no link to your name. Copies held by Twilio, or already exported by the agent, are outside ScanIn's control: ask them directly.</li>
  <li><b>See or correct your information:</b> ask the agent who ran the open house{{ '' }}@if (filled($email)) or write to us at <a href="mailto:{{ $email }}">{{ $email }}</a>@endif. We aim to answer within 30 days. Because the agent is responsible for your information, we may pass your request to them and help them answer it.</li>
  <li><b>Complain:</b> you can contact the Office of the Privacy Commissioner of Canada.</li>
</ul>

<h3>Cookies</h3>
<p>The check-in and feedback pages set small cookies that are needed for the form to work and to protect it against forged submissions. They expire after two hours and are not used for tracking or advertising. The check-in and feedback pages, the login pages and the agent dashboard contain no advertising or third-party tracking scripts.</p>
<p>The public home page at scanin.ca uses Google Tag Manager and Google Analytics to count visits to that page. Google may set cookies in your browser and receive your IP address and browser details when you open it. This applies only to the home page, not to the pages visitors use to check in. To avoid it, block these cookies in your browser or use a tracking blocker.</p>

<h2>3. If you are an agent</h2>
<p><b>What we collect:</b> your name, email, phone number, brokerage, password (stored only in scrambled form), the open houses you create, the visitor records they produce, and technical logs.</p>
<p><b>Why:</b> to run your account, to send you account emails such as password resets, to keep the service secure, and to understand how ScanIn is used.</p>
<p><b>Your responsibilities:</b> you are responsible for using visitor information lawfully. See the <a href="{{ route('terms') }}">Terms of service</a>.</p>
<p><b>Who sees your details:</b> your name, brokerage and email are shown to visitors on your check-in pages and on the privacy page they open from them, so they know who to contact. Account emails (such as password resets) are sent through an email provider that may be located outside Canada.</p>
<p><b>Cookies:</b> when you are logged in we use a session cookie that is necessary for the site to work.</p>

<h2>4. Security</h2>
<p>Data is sent over HTTPS and passwords are hashed. Agents can only see their own open houses and visitors. No system is perfectly secure. If a security breach could cause real harm, we will tell the agent without delay so that they, as the organization responsible for your information, can notify the people affected and the Privacy Commissioner as the law requires.</p>

<h2>5. Changes</h2>
<p>We will post changes on this page and update the date above. We will tell agents about material changes by email.</p>

<h2>6. Contact</h2>
<p>{{ $operator }}@if (filled($address)), {{ $address }}@endif
@if (filled(config('scanin.contact_email'))), <a href="mailto:{{ config('scanin.contact_email') }}">{{ config('scanin.contact_email') }}</a>@endif.</p>
@endsection
