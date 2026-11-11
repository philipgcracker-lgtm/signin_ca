<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') | ScanIn</title>
<meta name="description" content="@yield('description')">
<style>
*{box-sizing:border-box}
body{margin:0;background:#f7f4ee;color:#1f2623;font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.wrap{max-width:720px;margin:0 auto;padding:16px}
.top{padding:8px 0 16px}
.top a{font-weight:600;letter-spacing:.2em;color:#1f2623;text-decoration:none}
article{background:#fbf9f5;border:1px solid #e0d9cc;border-radius:12px;padding:24px}
h1{font-size:1.6rem;line-height:1.25;margin:0 0 4px}
h2{font-size:1.15rem;margin:28px 0 6px}
h3{font-size:1rem;margin:18px 0 4px}
.upd{color:#5b635d;margin:0 0 16px;font-size:.9rem}
ul{padding-left:20px;margin:6px 0}
li{margin:3px 0}
a{color:#3a5a3e}
a:focus-visible{outline:2px solid #4a6e4e;outline-offset:3px}
.foot{text-align:center;font-size:.8rem;color:#5b635d;margin:16px 0}
.foot a{color:#5b635d}
</style>
</head>
<body>
<div class="wrap">
<div class="top"><a href="/">SCANIN</a></div>
<article>
@yield('content')
</article>
<p class="foot"><a href="{{ route('privacy') }}">Privacy policy</a> &middot; <a href="{{ route('terms') }}">Terms of service</a></p>
</div>
</body>
</html>
