<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public privacy and terms pages. The privacy page can be opened from a check-in
 * page (?e=<event slug>) so a visitor sees who to contact: the agent who ran that
 * open house. Only the agent's public contact details are used.
 */
class LegalController extends Controller
{
    public function privacy(Request $request): View
    {
        $agent = null;

        if (is_string($slug = $request->query('e')) && $slug !== '') {
            $event = Event::query()->where('slug', $slug)->with('agent')->first();

            // Same rule as the check-in page: a cancelled event or a suspended or removed
            // agent is not shown, so their details are not exposed through an old link.
            if ($event && $event->agent && ! $event->isCancelled() && ! $event->isSuspended()) {
                $agent = $event->agent;
            }
        }

        return view('legal.privacy', ['agent' => $agent]);
    }

    public function terms(): View
    {
        return view('legal.terms');
    }
}
