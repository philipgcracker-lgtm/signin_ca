<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualVisitorRequest;
use App\Jobs\SendQueuedMessage;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\CheckinMessage;
use App\Services\PhoneNumber;
use App\Sms\SmsProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Agent adds a visitor from a paper sign-in sheet. The text is queued only when
 * the agent says the visitor ticked "OK to text" on the sheet; that tick is
 * recorded against the paper wording version, with no IP or user agent (there
 * is nothing to capture on paper).
 */
class ManualVisitorController extends Controller
{
    public function create(Request $request, int $event): View
    {
        $event = $this->ownedEvent($request, $event);
        $paper = ConsentText::paper();

        return view('events.visitors.create', [
            'event' => $event,
            'paperWording' => $paper?->renderFor($event->agent),
            'paperVersion' => $paper?->version,
        ]);
    }

    public function store(ManualVisitorRequest $request, int $event, SmsProvider $provider): RedirectResponse
    {
        $event = $this->ownedEvent($request, $event);
        $data = $request->validated();
        $phone = PhoneNumber::toE164($data['phone']);
        $wantsText = (bool) ($data['sms_ok'] ?? false);

        $paper = ConsentText::paper();
        abort_if($wantsText && $paper === null, 503, 'Paper consent wording is not available.');

        $result = DB::transaction(function () use ($data, $phone, $event, $wantsText, $paper, $provider, $request) {
            $exists = Visitor::query()
                ->where('event_id', $event->id)
                ->where('phone_e164', $phone)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return false;
            }

            $visitor = Visitor::create([
                'event_id' => $event->id,
                'source' => 'paper',
                'full_name' => trim($data['full_name']),
                'phone_e164' => $phone,
                'email' => $data['email'] ?? null,
                'working_with_agent' => isset($data['working_with_agent']) ? (bool) $data['working_with_agent'] : null,
                'buying_timeline' => $data['buying_timeline'] ?? null,
                'pre_approved' => isset($data['pre_approved']) ? (bool) $data['pre_approved'] : null,
            ]);

            if (! $wantsText) {
                return null;
            }

            Consent::create([
                'visitor_id' => $visitor->id,
                'agent_id' => $request->user()->id,
                'consent_text_id' => $paper->id,
                'channel' => 'sms',
                'purpose' => 'checkin',
                'granted' => true,
            ]);

            $link = ShortLink::createFor($event->listing_url, $event->id, $visitor->id);

            return Message::create([
                'visitor_id' => $visitor->id,
                'event_id' => $event->id,
                'direction' => 'out',
                'channel' => 'sms',
                'kind' => 'checkin',
                'to_addr' => $phone,
                'from_addr' => $provider->from(),
                'body' => CheckinMessage::build($event, $event->agent, $link->url()),
                'provider' => $provider->name(),
                'status' => 'queued',
            ]);
        });

        if ($result === false) {
            return back()->withInput()->withErrors(['phone' => "That number is already on this open house's list."]);
        }

        if ($result instanceof Message) {
            SendQueuedMessage::dispatchAfterResponse($result->id);
        }

        return redirect()
            ->route('visitors.create', $event->id)
            ->with('status', trim($data['full_name']).' added'.($result instanceof Message ? '; the listing text is on its way.' : '; no text sent.'));
    }

    /**
     * Agent-scoped lookup: another agent's event is a 404, never a 403.
     */
    private function ownedEvent(Request $request, int $id): Event
    {
        return Event::query()
            ->where('agent_id', $request->user()->id)
            ->with('agent')
            ->findOrFail($id);
    }
}
