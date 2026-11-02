<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkController extends Controller
{
    public function show(string $code): RedirectResponse
    {
        $link = ShortLink::query()->find($code);

        abort_if($link === null, 404);

        $link->increment('clicks');

        // 302, not 301: a cached permanent redirect would stop clicks being counted.
        return redirect()->away($link->target_url);
    }
}
