<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Agent creates an open house. The event always belongs to the signed-in agent
 * (agent_id is never read from the request). Each event gets its own slug, so
 * its QR (/e/{slug}) is unique.
 */
class EventController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->isApproved()) {
            return $this->pending();
        }

        return view('events.create');
    }

    public function store(EventRequest $request): RedirectResponse
    {
        if (! $request->user()->isApproved()) {
            return $this->pending();
        }

        $data = $request->validated();
        $tz = config('app.timezone');
        $start = $request->startsAt();

        $postal = isset($data['postal_code']) && $data['postal_code'] !== null
            ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['postal_code']), 0, 3).' '.substr(preg_replace('/[^A-Za-z0-9]/', '', $data['postal_code']), 3))
            : null;

        $event = Event::create([
            'agent_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($data['address_street'], $start),
            'address_street' => trim($data['address_street']),
            'city' => trim($data['city']),
            'province' => 'ON',
            'postal_code' => $postal,
            'price' => $data['price'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'blurb' => filled($data['blurb'] ?? null) ? trim($data['blurb']) : null,
            'listing_url' => $data['listing_url'],
            'event_start' => $start->copy()->setTimezone($tz),
            'event_end' => $request->endsAt()->setTimezone($tz),
            'status' => 'upcoming',
        ]);

        return redirect()->route('events.show', $event->id)
            ->with('status', 'Open house created. Print the door sign or download the QR below.');
    }

    private function pending(): RedirectResponse
    {
        return redirect()->route('dashboard')
            ->with('error', 'Your account is waiting for approval before you can create open houses.');
    }

    /**
     * "74-pinehurst-drive-nov8"; a short random tail is added only if that is taken.
     */
    private function uniqueSlug(string $street, \Illuminate\Support\Carbon $start): string
    {
        $base = Str::limit(Str::slug($street), 40, '').'-'.strtolower($start->format('Md'));
        $base = trim($base, '-');
        $slug = $base;

        while (Event::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
