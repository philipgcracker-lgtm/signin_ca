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
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        return view('events.create');
    }

    public function store(EventRequest $request): RedirectResponse
    {
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        $event = Event::create([
            'agent_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($request->validated('address_street'), $request->startsAt()),
            'province' => 'ON',
            'status' => 'upcoming',
        ] + $this->fields($request));

        return redirect()->route('events.show', $event->id)
            ->with('status', 'Open house created. Print the door sign or download the QR below.');
    }

    public function edit(Request $request, int $event): View|RedirectResponse
    {
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        $event = $this->owned($request, $event);

        if ($event->isCancelled()) {
            return $this->cancelledNotice($event);
        }

        return view('events.edit', ['event' => $event]);
    }

    /**
     * The slug never changes, so a printed QR or door sign keeps working after the
     * address or date is corrected.
     */
    public function update(EventRequest $request, int $event): RedirectResponse
    {
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        $event = $this->owned($request, $event);

        if ($event->isCancelled()) {
            return $this->cancelledNotice($event);
        }

        $event->update($this->fields($request));

        return redirect()->route('events.show', $event->id)->with('status', 'Open house updated.');
    }

    /** Cancel keeps every check-in; the check-in page and QR stop working (404). */
    public function cancel(Request $request, int $event): RedirectResponse
    {
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        $event = $this->owned($request, $event);
        $event->update(['status' => 'cancelled']);

        return redirect()->route('events.show', $event->id)
            ->with('status', 'Open house cancelled. The check-in page is turned off. Your list of check-ins is kept.');
    }

    /** For a cancel that was a mistake. */
    public function reopen(Request $request, int $event): RedirectResponse
    {
        if (! $request->user()->canCreateEvents()) {
            return $this->pending($request);
        }

        $event = $this->owned($request, $event);
        $event->update(['status' => $event->event_start->isFuture() ? 'upcoming' : ($event->hasEnded() ? 'ended' : 'live')]);

        return redirect()->route('events.show', $event->id)
            ->with('status', 'Open house reopened. The check-in page is back on.');
    }

    private function owned(Request $request, int $id): Event
    {
        return Event::query()->where('agent_id', $request->user()->id)->findOrFail($id);
    }

    private function cancelledNotice(Event $event): RedirectResponse
    {
        return redirect()->route('events.show', $event->id)
            ->with('error', 'This open house is cancelled. Reopen it to make changes.');
    }

    /**
     * The agent-editable columns. Never includes agent_id, slug or status.
     *
     * @return array<string, mixed>
     */
    private function fields(EventRequest $request): array
    {
        $data = $request->validated();
        $tz = config('app.timezone');

        $postal = filled($data['postal_code'] ?? null)
            ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['postal_code']), 0, 3).' '.substr(preg_replace('/[^A-Za-z0-9]/', '', $data['postal_code']), 3))
            : null;

        return [
            'address_street' => trim($data['address_street']),
            'city' => trim($data['city']),
            'postal_code' => $postal,
            'price' => $data['price'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'blurb' => filled($data['blurb'] ?? null) ? trim($data['blurb']) : null,
            'listing_url' => $data['listing_url'],
            'event_start' => $request->startsAt()->copy()->setTimezone($tz),
            'event_end' => $request->endsAt()->setTimezone($tz),
        ];
    }

    private function pending(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard')
            ->with('error', $request->user()->isSuspended()
                ? 'Your account is suspended. Contact ScanIn to restore it.'
                : 'Your account is waiting for approval before you can create open houses.');
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
