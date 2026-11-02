<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckinRequest;
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
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public visitor check-in. No authentication; protected by rate limits, a
 * honeypot field and the one-SMS-per-phone-per-event rule.
 */
class CheckinController extends Controller
{
    public function show(Event $event): View|RedirectResponse
    {
        abort_if($event->isCancelled(), 404);

        if ($event->hasEnded()) {
            return redirect()->away($event->listing_url);
        }

        $consent = ConsentText::current();

        // Never show a form without versioned consent wording to record against.
        abort_if($consent === null, 503, 'Check-in is not available right now.');

        $event->loadMissing('agent');

        return view('checkin.show', [
            'event' => $event,
            'agent' => $event->agent,
            'consent' => $consent,
            'consentBody' => $consent->renderFor($event->agent),
        ]);
    }

    public function store(CheckinRequest $request, Event $event, SmsProvider $provider): RedirectResponse
    {
        abort_if($event->isCancelled(), 404);

        if ($event->hasEnded()) {
            return redirect()->away($event->listing_url);
        }

        $thanks = redirect()->route('checkin.thanks', $event);

        // Honeypot: real visitors never see or fill this field. Look successful, store nothing.
        if (filled($request->input('website'))) {
            return $thanks;
        }

        $data = $request->validated();
        $phone = PhoneNumber::toE164($data['phone']);

        // Same phone, same event: no second visitor and no second text.
        $already = Visitor::query()
            ->where('event_id', $event->id)
            ->where('phone_e164', $phone)
            ->exists();

        if ($already) {
            return $thanks;
        }

        $consentText = ConsentText::query()->findOrFail($data['consent_text_id']);
        $agent = $event->agent;
        $ip = $request->ip() ? @inet_pton($request->ip()) : false;
        $userAgent = Str::limit((string) $request->userAgent(), 255, '');

        $message = DB::transaction(function () use ($data, $phone, $event, $agent, $consentText, $ip, $userAgent, $provider) {
            $visitor = Visitor::create([
                'event_id' => $event->id,
                'full_name' => trim($data['full_name']),
                'phone_e164' => $phone,
                'email' => $data['email'] ?? null,
                'working_with_agent' => isset($data['working_with_agent']) ? (bool) $data['working_with_agent'] : null,
                'buying_timeline' => $data['buying_timeline'] ?? null,
                'pre_approved' => isset($data['pre_approved']) ? (bool) $data['pre_approved'] : null,
                'ip_address' => $ip === false ? null : $ip,
                'user_agent' => $userAgent,
            ]);

            Consent::create([
                'visitor_id' => $visitor->id,
                'agent_id' => $agent->id,
                'consent_text_id' => $consentText->id,
                'channel' => 'sms',
                'granted' => true,
                'ip_address' => $ip === false ? null : $ip,
                'user_agent' => $userAgent,
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
                'body' => CheckinMessage::build($event, $agent, $link->url()),
                'provider' => $provider->name(),
                'status' => 'queued',
            ]);
        });

        // Runs in this request, after the response is sent: the visitor does not wait for Twilio.
        SendQueuedMessage::dispatchAfterResponse($message->id);

        return $thanks;
    }

    public function thanks(Event $event): View
    {
        abort_if($event->isCancelled(), 404);

        return view('checkin.thanks', ['event' => $event]);
    }
}
