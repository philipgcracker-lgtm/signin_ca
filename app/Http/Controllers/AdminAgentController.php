<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Owner-only admin: who is signed up, who is approved or on Pro, who is
 * sending how much, and the switches to approve, suspend or comp an agent.
 *
 * Deliberately shows counts only. Visitor names and phone numbers belong to the
 * agent and are never displayed here.
 */
class AdminAgentController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $agents = Agent::query()
            ->withCount('events')
            ->orderByRaw('approved_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $texts = $this->textStats($monthStart);

        $checkins = Visitor::query()
            ->join('events', 'events.id', '=', 'visitors.event_id')
            ->groupBy('events.agent_id')
            ->selectRaw('events.agent_id as agent_id, COUNT(*) as n')
            ->pluck('n', 'agent_id');

        return view('admin.agents.index', [
            'agents' => $agents,
            'texts' => $texts,
            'checkins' => $checkins,
            'pending' => $agents->filter(fn (Agent $a) => ! $a->isApproved())->count(),
        ]);
    }

    public function show(Agent $agent): View
    {
        $events = $agent->events()->withCount('visitors')->orderByDesc('event_start')->get();

        $all = $this->textStats(null)->get($agent->id);
        $month = $this->textStats(now()->startOfMonth())->get($agent->id);

        return view('admin.agents.show', [
            'agent' => $agent,
            'events' => $events,
            'textsAll' => $all,
            'textsMonth' => $month,
        ]);
    }

    public function approve(Request $request, Agent $agent): RedirectResponse
    {
        $agent->forceFill(['approved_at' => $agent->approved_at ?? now()])->save();

        return $this->done($request, $agent, 'approve', $agent->email.' approved.');
    }

    public function revoke(Request $request, Agent $agent): RedirectResponse
    {
        if ($this->isSelf($request, $agent)) {
            return $this->refuse('You cannot revoke your own approval.');
        }

        $agent->forceFill(['approved_at' => null])->save();

        return $this->done($request, $agent, 'revoke', $agent->email.' approval revoked.');
    }

    public function suspend(Request $request, Agent $agent): RedirectResponse
    {
        if ($this->isSelf($request, $agent)) {
            return $this->refuse('You cannot suspend yourself.');
        }

        $agent->forceFill(['suspended_at' => $agent->suspended_at ?? now()])->save();

        return $this->done($request, $agent, 'suspend', $agent->email.' suspended. Their check-in pages, links and texts are off.');
    }

    public function unsuspend(Request $request, Agent $agent): RedirectResponse
    {
        $agent->forceFill(['suspended_at' => null])->save();

        return $this->done($request, $agent, 'unsuspend', $agent->email.' restored.');
    }

    public function tier(Request $request, Agent $agent): RedirectResponse
    {
        $data = $request->validate(['tier' => ['required', 'in:free,pro']]);

        $agent->forceFill([
            'subscription_tier' => $data['tier'],
            // A tier set here is a manual grant, not a Stripe subscription.
            'comped_at' => $data['tier'] === 'pro' ? ($agent->comped_at ?? now()) : null,
        ])->save();

        return $this->done($request, $agent, 'tier:'.$data['tier'], $agent->email.' set to '.($data['tier'] === 'pro' ? 'Pro (comped)' : 'Free').'.');
    }

    /**
     * Outbound text counts per agent: total sent and failed. Counts only.
     *
     * @return \Illuminate\Support\Collection<int, object{agent_id:int,sent:int,failed:int}>
     */
    private function textStats(?\Illuminate\Support\Carbon $since): \Illuminate\Support\Collection
    {
        $query = Message::query()
            ->join('events', 'events.id', '=', 'messages.event_id')
            ->where('messages.direction', 'out')
            ->groupBy('events.agent_id')
            ->selectRaw("events.agent_id as agent_id, COUNT(*) as sent, SUM(CASE WHEN messages.status IN ('failed','undelivered') THEN 1 ELSE 0 END) as failed");

        if ($since !== null) {
            $query->where('messages.created_at', '>=', $since);
        }

        return $query->get()->keyBy('agent_id')->map(fn ($r) => (object) [
            'agent_id' => (int) $r->agent_id,
            'sent' => (int) $r->sent,
            'failed' => (int) $r->failed,
        ]);
    }

    private function isSelf(Request $request, Agent $agent): bool
    {
        return $request->user()->is($agent);
    }

    private function refuse(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }

    private function done(Request $request, Agent $agent, string $action, string $message): RedirectResponse
    {
        Log::info('admin action', ['admin' => $request->user()->id, 'agent' => $agent->id, 'action' => $action, 'ip' => $request->ip()]);

        return back()->with('status', $message);
    }
}
