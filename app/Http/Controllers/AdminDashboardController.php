<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Consent;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\SchedulerHeartbeat;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Owner-only platform stats. Counts and totals only: no visitor names, phone
 * numbers or message bodies are ever read into this page.
 */
class AdminDashboardController extends Controller
{
    private const DAYS = 30;

    public function index(): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $since = now(Event::DISPLAY_TIMEZONE)->subDays(self::DAYS - 1)->startOfDay()->setTimezone(config('app.timezone'));

        // Agents
        $agents = [
            'total' => Agent::count(),
            'pending' => Agent::whereNull('approved_at')->count(),
            'suspended' => Agent::whereNotNull('suspended_at')->count(),
            'pro' => Agent::where('subscription_tier', 'pro')->count(),
            'comped' => Agent::whereNotNull('comped_at')->count(),
            'new_30d' => Agent::where('created_at', '>=', $since)->count(),
        ];

        // Events, by the clock (status column can lag).
        $live = Event::where('status', '!=', 'cancelled');
        $events = [
            'total' => Event::count(),
            'upcoming' => (clone $live)->where('event_start', '>', $now)->count(),
            'live' => (clone $live)->where('event_start', '<=', $now)->where('event_end', '>', $now)->count(),
            'ended' => (clone $live)->where('event_end', '<=', $now)->count(),
            'new_30d' => Event::where('created_at', '>=', $since)->count(),
        ];

        // Check-ins
        $visitors = [
            'total' => Visitor::count(),
            'month' => Visitor::where('created_at', '>=', $monthStart)->count(),
            'opted_out' => Visitor::where('sms_opt_out', true)->count(),
            'feedback' => Visitor::whereNotNull('feedback_score')->count(),
            'avg_score' => Visitor::whereNotNull('feedback_score')->avg('feedback_score'),
        ];
        $consented = Consent::where('granted', true)->whereNull('revoked_at')->distinct()->count('visitor_id');
        $visitors['consent_rate'] = $visitors['total'] > 0 ? (int) round($consented / $visitors['total'] * 100) : null;

        // Texts (outbound)
        $out = fn () => Message::where('direction', 'out');
        $texts = [
            'total' => $out()->count(),
            'month' => $out()->where('created_at', '>=', $monthStart)->count(),
            'failed_month' => $out()->where('created_at', '>=', $monthStart)->whereIn('status', ['failed', 'undelivered'])->count(),
            'cost_month_cents' => (int) $out()->where('created_at', '>=', $monthStart)->sum('cost_cents'),
        ];
        $texts['failure_rate'] = $texts['month'] > 0 ? (int) round($texts['failed_month'] / $texts['month'] * 100) : null;

        // Listing-link engagement
        $links = ShortLink::listing()->whereNotNull('visitor_id');
        $linkTotal = (clone $links)->count();
        $linkClicked = (clone $links)->where('clicks', '>', 0)->count();
        $engagement = [
            'links' => $linkTotal,
            'clicked' => $linkClicked,
            'rate' => $linkTotal > 0 ? (int) round($linkClicked / $linkTotal * 100) : null,
            'clicks' => (int) (clone $links)->sum('clicks'),
        ];

        // 30-day charts, bucketed by Toronto calendar day.
        $checkinSeries = $this->series(Visitor::where('created_at', '>=', $since)->pluck('created_at'));
        $textSeries = $this->series(Message::where('direction', 'out')->where('created_at', '>=', $since)->pluck('created_at'));

        // Top agents by check-ins in the window.
        $top = Visitor::query()
            ->join('events', 'events.id', '=', 'visitors.event_id')
            ->where('visitors.created_at', '>=', $since)
            ->groupBy('events.agent_id')
            ->selectRaw('events.agent_id as agent_id, COUNT(*) as n')
            ->orderByDesc('n')
            ->limit(5)
            ->get();
        $names = Agent::whereIn('id', $top->pluck('agent_id'))->get()->keyBy('id');

        return view('admin.dashboard', [
            'agents' => $agents,
            'events' => $events,
            'visitors' => $visitors,
            'texts' => $texts,
            'engagement' => $engagement,
            'checkinSeries' => $checkinSeries,
            'textSeries' => $textSeries,
            'top' => $top->map(fn ($r) => (object) ['agent' => $names->get($r->agent_id), 'n' => (int) $r->n])->filter(fn ($r) => $r->agent),
            'days' => self::DAYS,
            'system' => [
                'running' => SchedulerHeartbeat::isRunning(),
                'last' => SchedulerHeartbeat::lastBeat(),
                // Texts that should have gone out within a minute or two but are still waiting.
                'stuck' => Message::query()->where('direction', 'out')->where('status', 'queued')->where('created_at', '<', now()->subMinutes(5))->count(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, \Illuminate\Support\Carbon>  $stamps
     * @return array<string, int> Y-m-d (Toronto) => count, oldest first, zero-filled
     */
    private function series(Collection $stamps): array
    {
        $tz = Event::DISPLAY_TIMEZONE;
        $out = [];

        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $out[now($tz)->subDays($i)->format('Y-m-d')] = 0;
        }

        foreach ($stamps as $stamp) {
            $key = $stamp->copy()->setTimezone($tz)->format('Y-m-d');
            if (array_key_exists($key, $out)) {
                $out[$key]++;
            }
        }

        return $out;
    }
}
