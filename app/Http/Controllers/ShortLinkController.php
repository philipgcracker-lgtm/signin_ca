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
        // A deleted agent's links stop too (the event outlives the agent row).
        if ($link->event_id !== null) {
            $event = Event::query()->with('agent')->find($link->event_id);
            abort_if($event !== null && ($event->isSuspended() || $event->agent === null), 404);
        }

        $link->increment('clicks');

        // 302, not 301: a cached permanent redirect would stop clicks being counted.
        return redirect()->away($link->target_url);
    }
}
