<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ScanIn: open house sign-in for Canadian agents</title>
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:title" content="ScanIn: open house sign-in for Canadian agents">
<meta property="og:description" content="Guests scan a QR code at the door and check in on their own phone. ScanIn texts them your listing link and keeps every guest, with consent on file, in one list.">
<meta name="twitter:card" content="summary">
<meta name="description" content="Guests scan a QR code at the door and check in on their own phone. ScanIn texts them your listing link and keeps every guest, with consent on file, in one list.">
<style>
:root{--ink:#1f2623;--dark:#1a1f1c;--cream:#f7f4ee;--paper:#fbf9f5;--line:#e0d9cc;--mute:#5b635d;--sage:#4a6e4e;--sage-l:#a9bfa6;--sage-bg:#e3eadf;--sand:#d4c0ae;--soft:#c9cfc9;--head:'Inter Tight',Inter,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--cream);color:var(--ink);font-family:Inter,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;font-size:16px;line-height:1.55}
a{color:var(--sage)}
a:focus-visible,button:focus-visible{outline:2px solid var(--sage);outline-offset:3px}
.hero :focus-visible,.card :focus-visible,.final :focus-visible,footer :focus-visible{outline-color:var(--sage-l)}
.skip-link{position:absolute;top:-100px;left:16px;background:var(--ink);color:#f5f0e8;padding:12px 18px;border-radius:6px;z-index:9999;text-decoration:none;font-weight:600}
.skip-link:focus{top:16px;outline-color:var(--sage-l)}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
h1,h2,h3{font-family:var(--head);font-weight:300;margin:0}
p{margin:0}
.wrap{max-width:1200px;margin:0 auto}
.btn{display:inline-flex;align-items:center;min-height:48px;padding:14px 22px;border-radius:8px;font-weight:600;font-size:15px;text-decoration:none}
.btn-light{background:#f5f0e8;color:var(--ink)}
.btn-dark{background:var(--ink);color:#f5f0e8;min-height:44px;padding:12px 20px;font-size:14px}
.btn-ghost{color:#f5f0e8;border:1px solid rgba(245,240,232,.35)}
.pill{display:inline-flex;align-items:center;gap:8px;background:rgba(245,240,232,.08);border:1px solid rgba(245,240,232,.14);border-radius:9999px;padding:6px 12px;font-size:12px;font-weight:500}
.tag{display:inline-block;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600;white-space:nowrap}

/* hero */
.hero{position:relative;overflow:hidden;color:#f5f0e8;padding:24px 24px 0;background:radial-gradient(70% 45% at 75% 100%,rgba(212,192,174,.55),rgba(212,192,174,0) 70%),radial-gradient(60% 40% at 15% 100%,rgba(74,110,78,.75),rgba(74,110,78,0) 70%),var(--dark)}
.qrtex{position:absolute;right:-40px;top:120px;opacity:.09;shape-rendering:crispEdges}
nav.top{position:relative;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:rgba(31,38,35,.85);border:1px solid rgba(245,240,232,.12);border-radius:14px;padding:10px 10px 10px 20px}
.logo{font-family:var(--head);font-weight:500;font-size:20px;letter-spacing:.2em;text-decoration:none;color:#f5f0e8}
.logo span{color:var(--sage-l)}
nav.top .links{display:flex;gap:28px;flex-wrap:wrap;font-size:14px;font-weight:500}
nav.top .links a{color:#f5f0e8;text-decoration:none}
.nav-cta{display:flex;gap:8px;align-items:center}
.nav-cta .login{color:#f5f0e8;text-decoration:none;font-size:14px;font-weight:500;padding:10px 12px}
.nav-cta .btn{min-height:44px;padding:10px 18px;font-size:14px}
.hero-grid{position:relative;display:flex;flex-wrap:wrap;gap:48px;align-items:flex-end;padding-top:88px}
.hero-l{flex:1.3 1 520px;min-width:0;display:flex;flex-direction:column;gap:28px}
.hero h1{font-size:clamp(44px,6.6vw,88px);line-height:1;letter-spacing:-.035em}
.hero .lede{font-size:18px;line-height:1.6;color:var(--soft);max-width:540px}
.hero-r{flex:1 1 360px;min-width:0;display:flex;flex-direction:column;gap:10px}
.toast{background:rgba(245,240,232,.94);color:var(--ink);border-radius:14px;padding:12px 14px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 18px 40px rgba(0,0,0,.3)}
.toast .ic{flex:none;width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center}
.toast .meta{display:flex;justify-content:space-between;gap:8px;font-size:12px;color:var(--mute)}
.toast b{display:block;font-size:14px;font-weight:600}
.toast small{display:block;font-size:13px;color:var(--mute)}
.eg{font-size:11px;letter-spacing:.08em;color:var(--soft);font-weight:600;margin-bottom:2px}
.calm{position:relative;text-align:center;padding:88px 0 40px}
.calm p{font-family:var(--head);font-weight:300;font-size:clamp(28px,3vw,40px);letter-spacing:-.02em}
.strip{position:relative;padding:24px 0 96px;display:flex;flex-wrap:wrap;gap:14px 40px;align-items:center;justify-content:space-between;border-top:1px solid rgba(245,240,232,.14)}
.strip .k{font-size:11px;font-weight:600;letter-spacing:.1em;color:var(--soft)}
.strip span.i{font-size:15px}

/* sections */
section.s{padding:104px 24px}
.center{display:flex;flex-direction:column;align-items:center;text-align:center;gap:16px}
.center h2{font-size:clamp(34px,4.4vw,60px);line-height:1.05;letter-spacing:-.03em}
.center p{font-size:17px;color:var(--mute);max-width:580px}
.strike{color:#555d57;text-decoration-color:currentColor;text-decoration:line-through;text-decoration-thickness:2px}
.flow{width:100%;margin-top:40px;background-color:var(--cream);background-image:radial-gradient(#d9d2c4 1.2px,transparent 1.2px);background-size:22px 22px;border-radius:22px;padding:48px 32px;display:flex;flex-wrap:wrap;gap:32px;align-items:center;justify-content:center}
.chips{flex:1 1 260px;display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.chip{background:var(--paper);border:1px solid var(--line);border-radius:9999px;padding:8px 14px;font-size:13px}
.qrbox{flex:none;width:112px;height:112px;border-radius:28px;background:var(--ink);display:flex;align-items:center;justify-content:center;box-shadow:0 18px 40px rgba(31,38,35,.25)}
.rows{flex:1 1 320px;display:flex;flex-direction:column;gap:8px;text-align:left}
.row{background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:10px 14px;display:flex;gap:10px;align-items:center;font-size:14px}
.row span:first-child{flex:1}
.cols{max-width:1200px;margin:56px auto 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));border-top:1px solid var(--line)}
.col{padding:32px 28px 0;border-right:1px solid var(--line);display:flex;flex-direction:column;gap:10px}
.col:last-child{border-right:0}
.col .n{font-family:var(--head);font-weight:300;font-size:40px;color:var(--sage)}
.col h3{font-weight:400;font-size:24px;letter-spacing:-.01em}
.col p{font-size:15px;color:var(--mute)}
.feat{max-width:1200px;margin:64px auto 0;display:flex;flex-direction:column;gap:72px}
.frow{display:flex;flex-wrap:wrap;gap:48px;align-items:center}
.frow.rev{flex-direction:row-reverse}
.ftxt{flex:1 1 340px;min-width:0}
.ftxt h3{font-size:clamp(28px,3vw,38px);line-height:1.12;letter-spacing:-.02em;margin-bottom:14px}
.ftxt p{font-size:16px;color:var(--mute)}
.card{flex:1.2 1 420px;min-width:0;min-height:320px;border-radius:22px;padding:30px;color:#f5f0e8;display:flex;flex-direction:column;gap:16px;background:radial-gradient(90% 55% at 50% 110%,rgba(212,192,174,.7),rgba(212,192,174,0) 70%),var(--ink)}
.card.g{background:radial-gradient(90% 55% at 50% 110%,rgba(74,110,78,.9),rgba(74,110,78,0) 70%),var(--ink)}
.card .hd{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;font-size:13px;color:var(--soft)}
.card .hd .tag{background:rgba(245,240,232,.12);color:#f5f0e8}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.stat{border:1px solid rgba(245,240,232,.14);border-radius:12px;padding:14px}
.stat b{display:block;font-family:var(--head);font-weight:300;font-size:34px}
.stat small{font-size:12px;color:var(--soft)}
.bub{max-width:88%;border-radius:14px;padding:10px 14px;font-size:14px}
.bub small{display:block;font-size:11px;font-weight:600;margin-bottom:2px}
.phone{background:#efeae1}
.field{background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:12px 14px;font-size:14px;color:var(--mute)}
.final{max-width:1200px;margin:0 auto;border-radius:22px;padding:96px 32px;color:#f5f0e8;text-align:center;display:flex;flex-direction:column;align-items:center;gap:22px;background:radial-gradient(60% 70% at 25% 110%,rgba(74,110,78,.85),rgba(74,110,78,0) 70%),radial-gradient(60% 70% at 80% 110%,rgba(212,192,174,.6),rgba(212,192,174,0) 70%),var(--dark)}
.final h2{font-size:clamp(38px,5vw,68px);line-height:1.02;letter-spacing:-.035em;max-width:860px}
.final p{font-size:17px;color:var(--soft)}
footer.f{padding:40px 24px 56px}
footer.f .wrap{display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between;align-items:center;font-size:14px;color:var(--mute)}
footer.f a{color:var(--mute)}
footer.f .logo{color:var(--ink);font-size:18px}
footer.f .logo span{color:var(--sage)}
@media (max-width:640px){.col{border-right:0;border-bottom:1px solid var(--line);padding:28px 0}.hero-grid{padding-top:56px}.card{padding:22px}}
</style>
</head>
<body>
<a href="#content" class="skip-link">Skip to main content</a>

<!-- ===== HERO ===== -->
<main>
<section class="hero" id="top">
<svg class="qrtex" aria-hidden="true" width="560" height="560" viewBox="0 0 28 28"><path fill="#f5f0e8" d="M0 0h7v7H0z M1 1v5h5V1z M2 2h3v3H2z M21 0h7v7h-7z M22 1v5h5V1z M23 2h3v3h-3z M0 21h7v7H0z M1 22v5h5v-5z M2 23h3v3H2z M9 0h2v1H9z M12 1h1v2h-1z M14 0h3v1h-3z M18 2h1v3h-1z M9 4h3v1H9z M14 3h2v2h-2z M9 7h1v2H9z M11 8h3v1h-3z M16 7h2v1h-2z M19 8h2v2h-2z M23 9h3v1h-3z M0 9h2v2H0z M3 10h3v1H3z M8 11h2v2H8z M12 11h1v3h-1z M15 10h2v2h-2z M20 12h3v1h-3z M25 11h3v2h-3z M1 13h3v1H1z M5 14h2v2H5z M10 15h3v1h-3z M15 14h1v3h-1z M18 15h2v1h-2z M22 14h2v2h-2z M26 15h2v1h-2z M0 17h2v2H0z M8 18h2v1H8z M12 17h2v2h-2z M17 18h3v1h-3z M22 18h1v3h-1z M24 19h3v1h-3z M9 21h1v3H9z M11 22h3v1h-3z M15 21h2v2h-2z M18 22h1v2h-1z M20 21h2v1h-2z M25 22h3v2h-3z M10 26h2v2h-2z M14 25h3v1h-3z M19 25h2v2h-2z M23 26h3v1h-3z"/></svg>

<div class="wrap">
<header>
<nav class="top" aria-label="Main">
<a class="logo" href="#top">SCAN<span>IN</span></a>
<div class="links">
<a href="#how">How it works</a>
<a href="#features">What you get</a>
</div>
<div class="nav-cta">
<a class="login" href="{{ route('login') }}">Log in</a>
<a class="btn btn-light" href="{{ config('scanin.registration_open') ? route('register') : route('login') }}">Get started</a>
</div>
</nav>
</header>

<div class="hero-grid" id="content" tabindex="-1">
<div class="hero-l">
<div style="display:flex;gap:10px;flex-wrap:wrap">
<span class="pill">Piloting at Niagara open houses this fall</span>
<span class="pill">Made in Canada</span>
</div>
<h1>Ready to stop retyping sign-in sheets at midnight?</h1>
<p class="lede">Guests scan a QR code at the door and check in on their own phone. ScanIn texts them your listing link and keeps every guest, with their consent on file, in one list.</p>
<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
<a class="btn btn-light" href="{{ config('scanin.registration_open') ? route('register') : route('login') }}">Get started</a>
<a class="btn btn-ghost" href="#how">See how it works</a>
</div>
</div>

<div class="hero-r" aria-label="Example check-ins">
<p class="eg">EXAMPLE</p>
<div class="toast" style="margin-right:32px">
<span class="ic" style="background:var(--ink);color:var(--sage-l)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z M20 20h1 M20 14v3"/></svg></span>
<div style="flex:1;min-width:0"><div class="meta"><span>Door QR</span><span>now</span></div><b>Priya S. signed in</b><small>Pre-approved. Not working with an agent.</small></div>
</div>
<div class="toast" style="margin-left:32px">
<span class="ic" style="background:var(--sage);color:#fff"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
<div style="flex:1;min-width:0"><div class="meta"><span>Text sent</span><span>1m</span></div><b>Marcus T. got your listing link</b><small>Delivered. Consent logged.</small></div>
</div>
<div class="toast" style="margin-right:12px">
<span class="ic" style="background:var(--sand);color:var(--ink)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
<div style="flex:1;min-width:0"><div class="meta"><span>Door QR</span><span>4m</span></div><b>Gary from next door signed in</b><small>Buying timeline: just browsing.</small></div>
</div>
</div>
</div>

<div class="calm">
<p>Relax, Sonya.</p>
<p style="color:var(--sage-l)">Everyone who signed in is already in your list.</p>
</div>

<div class="strip">
<span class="k">BUILT FOR HOW CANADIAN AGENTS WORK</span>
<span class="i">Consent at sign-in</span>
<span class="i">Opt-ins logged with a timestamp</span>
<span class="i">Your own listing link</span>
<span class="i">Any phone camera</span>
</div>
</div>
</section>

<!-- ===== CLIPBOARD -> LIST ===== -->
<section class="s">
<div class="wrap center">
<h2><span class="sr-only">Instead of a clipboard: </span><del class="strike" aria-hidden="true">Clipboard</del> One list.</h2>
<p>Every guest who walks through the door lands in one place: name, mobile, consent on file, and what they told you they want.</p>
<div class="flow">
<div class="chips" aria-hidden="true">
<span class="chip" style="transform:rotate(-4deg)">J. Sm…?</span>
<span class="chip" style="transform:rotate(3deg)">mike@gmai</span>
<span class="chip" style="transform:rotate(-2deg)">905-55_-____</span>
<span class="chip" style="transform:rotate(5deg)">“just looking”</span>
<span class="chip" style="transform:rotate(-6deg)">Okafor family</span>
<span class="chip" style="transform:rotate(2deg)">Gary (neighbour)</span>
</div>
<div class="qrbox"><svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--sage-l)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="ScanIn"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z M20 20h1 M17 20h1 M20 14v3"/></svg></div>
<div class="rows">
<div class="row"><span><strong>Priya S.</strong> · pre-approved, no agent</span><span class="tag" style="background:var(--ink);color:#f5f0e8">PRE-APPROVED</span></div>
<div class="row"><span><strong>Okafor family</strong> · buying in 1–3 months</span><span class="tag" style="background:var(--sage-bg);color:#3b5a3f">1–3 MONTHS</span></div>
<div class="row"><span><strong>Mike C.</strong> · 6+ months out</span><span class="tag" style="background:var(--sage-bg);color:#3b5a3f">6+ MONTHS</span></div>
<div class="row"><span style="color:var(--mute)"><strong>Dana R.</strong> · working with an agent</span><span class="tag" style="background:#ece6db;color:var(--mute)">HAS AGENT</span></div>
</div>
</div>
<p style="font-size:12px;color:var(--mute);margin-top:4px">Sample names. Guests choose whether to answer the optional questions.</p>
</div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="s" id="how" style="padding-top:32px">
<div class="wrap center">
<h2>Live before Sunday.</h2>
<p>A few minutes to set up. Nothing to carry or wipe down.</p>
<a class="btn btn-dark" href="{{ config('scanin.registration_open') ? route('register') : route('login') }}" style="margin-top:8px">Get started</a>
</div>
<div class="cols">
<div class="col"><span class="n">01</span><h3>Add the open house.</h3><p>Address, time, and a link to your own listing page. No data feed to hook up.</p></div>
<div class="col"><span class="n">02</span><h3>Print the door sign.</h3><p>A clean QR sign with your name and brokerage on it. Front door, kitchen counter, done.</p></div>
<div class="col"><span class="n">03</span><h3>Guests sign in.</h3><p>They scan, fill in a short form on their own phone, and get your listing link by text.</p></div>
</div>
</section>

<!-- ===== WHAT YOU GET ===== -->
<section class="s" id="features" style="padding-top:0">
<div class="wrap center">
<h2>No app. No tablet.<br>Just their camera.</h2>
<p>Nothing for guests to download. The form loads fast, even with weak signal at the door.</p>
</div>

<div class="feat">
<div class="frow">
<div class="ftxt">
<h3>The listing link lands in their pocket.</h3>
<p>Each guest who agrees gets one text with your listing page, signed with your name and brokerage. Replying STOP opts them out, instantly.</p>
</div>
<div class="card g" aria-label="Example text message">
<div class="hd"><span>Example text message</span></div>
<div class="bub" style="align-self:flex-start;background:#ece6db;color:var(--ink);border-radius:14px 14px 14px 4px"><small style="color:var(--sage)">SCANIN</small>Thanks for visiting 42 Maple Crescent. Listing details: scanin.ca/s/a1b2c3 Reply STOP to opt out. -Your Name, Your Brokerage</div>
<div class="hd" style="margin-top:auto"><span>Consent wording, version and time are saved with every check-in.</span></div>
</div>
</div>

<div class="frow rev">
<div class="ftxt">
<h3>A seller recap, without the spreadsheet.</h3>
<p>Visitor count, how many were pre-approved or already had an agent, buying timelines, and what guests said about the home, in a clean PDF for your seller. <strong>Coming soon.</strong></p>
</div>
<div class="card" aria-label="Sample seller recap">
<div class="hd"><span>Sample seller recap · 42 Maple Crescent</span><span class="tag">COMING SOON</span></div>
<div class="stats">
<div class="stat"><b>18</b><small>Visitors</small></div>
<div class="stat"><b>5</b><small>Pre-approved</small></div>
<div class="stat"><b>4.0</b><small>Avg. rating</small></div>
</div>
<p style="font-size:14px;color:#e6e1d8">“Loved the kitchen. Worried about the busy street.” <span style="color:var(--soft)">(sample comment)</span></p>
</div>
</div>
</div>
</section>

<!-- ===== FINAL CTA ===== -->
<section style="padding:0 24px 24px">
<div class="final">
<h2>Your next open house is the easiest place to start.</h2>
<p>Set it up this week. Every guest in one place by Sunday night.</p>
<a class="btn btn-light" href="{{ config('scanin.registration_open') ? route('register') : route('login') }}">Get started</a>
</div>
</section>
</main>

<footer class="f">
<div class="wrap">
<a class="logo" href="#top">SCAN<span>IN</span></a>
<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center">
<a href="{{ route('login') }}">Log in</a>
<a href="{{ route('privacy') }}">Privacy</a>
<a href="{{ route('terms') }}">Terms</a>
<span>© {{ date('Y') }} ScanIn</span>
</div>
</div>
</footer>

</body>
</html>
