<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR for an event's public check-in page (/e/{slug}): an SVG download and a
 * printable door sign. Agent-scoped like every other agent page.
 */
class EventQrController extends Controller
{
    public function svg(Request $request, int $event): Response
    {
        $event = $this->ownedEvent($request, $event);

        $headers = ['Content-Type' => 'image/svg+xml'];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="scanin-'.$event->slug.'-qr.svg"';
        }

        return response($this->svgFor($event), 200, $headers);
    }

    public function sign(Request $request, int $event): View
    {
        $event = $this->ownedEvent($request, $event);

        return view('events.sign', [
            'event' => $event,
            'agent' => $event->agent,
            'qr' => $this->svgFor($event),
            'url' => route('checkin.show', $event->slug),
        ]);
    }

    /**
     * Error correction Q and a 4-module quiet zone so it scans from a door sign
     * at a distance or a bit scuffed.
     */
    private function svgFor(Event $event): string
    {
        return (string) QrCode::format('svg')
            ->errorCorrection('Q')
            ->margin(4)
            ->size(600)
            ->generate(route('checkin.show', $event->slug));
    }

    private function ownedEvent(Request $request, int $id): Event
    {
        return Event::query()
            ->where('agent_id', $request->user()->id)
            ->with('agent')
            ->findOrFail($id);
    }
}
