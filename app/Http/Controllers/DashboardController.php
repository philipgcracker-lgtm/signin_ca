<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agent dashboard: their events, and the visitor list for one event.
 * Every query is scoped to the signed-in agent; an event ID in the URL is never
 * trusted on its own, so another agent's event is a 404.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::query()
            ->where('agent_id', $request->user()->id)
            ->withCount('visitors')
            ->orderByDesc('event_start')
            ->get();

        return view('dashboard', ['events' => $events]);
    }

    public function show(Request $request, int $event): View
    {
        $event = Event::query()
            ->where('agent_id', $request->user()->id)
            ->findOrFail($event);

        $visitors = $event->visitors()
            ->with(['consents', 'messages', 'shortLinks' => fn ($q) => $q->listing()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $rows = $visitors->map(function ($visitor) {
            $consent = $visitor->consents->sortByDesc('id')->first();
            $text = $visitor->messages
                ->where('direction', 'out')
                ->where('kind', 'checkin')
                ->sortByDesc('id')
                ->first();

            return (object) [
                'visitor' => $visitor,
                'consent' => $consent,
                'text' => $text,
                'has_link' => $visitor->shortLinks->isNotEmpty(),
                'clicks' => (int) $visitor->shortLinks->sum('clicks'),
            ];
        });

        $summary = [
            'checkins' => $rows->count(),
            'consented' => $rows->filter(fn ($r) => $r->consent?->granted && ! $r->consent->revoked_at)->count(),
            'delivered' => $rows->filter(fn ($r) => in_array($r->text?->status, ['sent', 'delivered'], true))->count(),
            'failed' => $rows->filter(fn ($r) => in_array($r->text?->status, ['failed', 'undelivered'], true))->count(),
            'clicked' => $rows->filter(fn ($r) => $r->clicks > 0)->count(),
            'opted_out' => $rows->filter(fn ($r) => $r->visitor->sms_opt_out)->count(),
            'feedback' => $rows->filter(fn ($r) => $r->visitor->feedback_score !== null)->count(),
            'avg_score' => $rows->pluck('visitor.feedback_score')->filter()->avg(),
        ];

        return view('events.show', [
            'event' => $event,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }
}
