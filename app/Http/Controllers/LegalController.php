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
            $agent = Event::query()->where('slug', $slug)->with('agent')->first()?->agent;
        }

        return view('legal.privacy', ['agent' => $agent]);
    }

    public function terms(): View
    {
        return view('legal.terms');
    }
}
