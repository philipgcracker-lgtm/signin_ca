<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Open house ended</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f4f6;color:#111827;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
main{max-width:30rem;margin:0 auto;padding:2rem 1rem}
.card{background:#fff;border-radius:12px;padding:1.5rem;box-shadow:0 1px 2px rgba(0,0,0,.08)}
h1{font-size:1.25rem;margin:0 0 .5rem}
p{margin:.5rem 0}
.host{font-weight:700;word-break:break-all}
a.btn{display:block;margin-top:1.25rem;padding:.85rem 1rem;border-radius:8px;background:#111827;color:#fff;text-align:center;font-weight:700;text-decoration:none}
.small{font-size:.85rem;color:#6b7280}
</style>
</head>
<body>
<main>
  <div class="card">
    <h1>This open house has ended</h1>
    <p>{{ $event->shortAddress() }}</p>
    <p>The agent's listing page is on another website:</p>
    <p class="host">{{ $host }}</p>
    <a class="btn" href="{{ $event->listing_url }}" rel="noopener noreferrer">Continue to the listing</a>
    <p class="small">You are leaving ScanIn. ScanIn does not control that site.</p>
  </div>
</main>
</body>
</html>
