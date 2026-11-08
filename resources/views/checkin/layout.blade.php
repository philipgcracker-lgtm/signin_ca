<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title')</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f4f6;color:#111827;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.wrap{max-width:480px;margin:0 auto;padding:16px}
.card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.12)}
h1{font-size:1.35rem;line-height:1.25;margin:0 0 4px}
.sub{color:#4b5563;margin:0 0 12px}
.facts{display:flex;flex-wrap:wrap;gap:6px 14px;margin:8px 0 12px;font-weight:600}
.blurb{margin:0 0 16px;color:#374151}
.photo{width:100%;height:auto;border-radius:8px;margin:0 0 14px;display:block}
.agent{display:flex;align-items:center;gap:12px;margin:0 0 16px;padding:10px 0;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb}
.agent img{width:52px;height:52px;border-radius:50%;object-fit:cover}
.agent b{display:block}
label{display:block;font-weight:600;margin:14px 0 4px}
input[type=text],input[type=tel],input[type=email],select{width:100%;font:inherit;padding:12px;border:1px solid #9ca3af;border-radius:8px;background:#fff}
input:focus,select:focus{outline:3px solid #93c5fd;border-color:#2563eb}
.consent{display:flex;gap:10px;align-items:flex-start;margin:18px 0 6px;padding:12px;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px}
.consent input{width:24px;height:24px;margin-top:2px;flex:none}
.consent span{font-size:.9rem}
.purpose{font-size:.8rem;color:#6b7280;margin:10px 0 0}
details{margin:14px 0 0}
summary{cursor:pointer;color:#1d4ed8;font-weight:600}
button{width:100%;margin-top:18px;padding:14px;border:0;border-radius:8px;background:#111827;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.err{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;padding:10px 12px;margin:0 0 14px}
.err ul{margin:0;padding-left:18px}
.hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.btn-link{display:block;text-align:center;margin-top:14px;padding:14px;border-radius:8px;background:#111827;color:#fff;font-weight:700;text-decoration:none}
.foot{text-align:center;font-size:.75rem;color:#9ca3af;margin-top:14px}
</style>
</head>
<body>
<div class="wrap">
@yield('content')
<p class="foot">Powered by ScanIn &middot; <a href="{{ route('privacy', isset($event) ? ['e' => $event->slug] : []) }}" target="_blank" rel="noopener" style="color:inherit">Privacy</a> &middot; <a href="{{ route('terms') }}" target="_blank" rel="noopener" style="color:inherit">Terms</a></p>
</div>
</body>
</html>
