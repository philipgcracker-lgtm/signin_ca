<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkController extends Controller
{
    public function show(string $code): RedirectResponse
    {
        $link = ShortLink::query()->find($code);

        abort_if($link === null, 404);

        // A suspended agent's links stop redirecting (this is the phishing kill switch).
        abort_if(
            $link->event_id !== null && Event::query()->with('agent')->find($link->event_id)?->isSuspended(),
            404,
        );

        $link->increment('clicks');

        // 302, not 301: a cached permanent redirect would stop clicks being counted.
        return redirect()->away($link->target_url);
    }
}
