<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckinRequest;
use App\Jobs\SendQueuedMessage;
use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\CheckinMessage;
use App\Services\PhoneNumber;
use App\Sms\SmsProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public visitor check-in. No authentication; protected by rate limits, a
 * honeypot field and the one-SMS-per-phone-per-event rule.
 */
class CheckinController extends Controller
{
    /** Check-ins accepted per event per hour (per-IP limit is the 'checkin' throttle). */
    private const EVENT_LIMIT_PER_HOUR = 300;

    /** After the event: tell the visitor where they are going before they leave ScanIn. */
    private function leaving(Event $event): Response
    {
        return response()->view('checkin.leaving', [
            'event' => $event,
            'host' => parse_url($event->listing_url, PHP_URL_HOST),
        ]);
    }

    /** A deleted agent leaves their event behind; the QR on a yard sign must 404, not 500. */
    private function unavailable(Event $event): bool
    {
        return $event->isCancelled() || $event->isSuspended() || $event->agent === null;
    }

    public function show(Event $event): View|Response
    {
        abort_if($this->unavailable($event), 404);

        if ($event->hasEnded()) {
            return $this->leaving($event);
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

    public function store(CheckinRequest $request, Event $event, SmsProvider $provider): RedirectResponse|Response
    {
        abort_if($this->unavailable($event), 404);

        if ($event->hasEnded()) {
            return $this->leaving($event);
        }

        // Per-event cap, keyed on the resolved event id (not the URL slug, which has
        // case/whitespace variants that all reach the same event).
        $eventKey = 'checkin-event:'.$event->id;
        abort_if(RateLimiter::tooManyAttempts($eventKey, self::EVENT_LIMIT_PER_HOUR), 429);
        RateLimiter::hit($eventKey, 3600);

        $thanks = redirect()->route('checkin.thanks', $event);

        // Honeypot: real visitors never see or fill this field. Look successful, store nothing.
        if (filled($request->input('website'))) {
            return $thanks;
        }

        $data = $request->validated();
        $phone = PhoneNumber::toE164($data['phone']);

        $consentText = ConsentText::query()->findOrFail($data['consent_text_id']);
        $agent = $event->agent;
        $ip = $request->ip() ? @inet_pton($request->ip()) : false;
        $userAgent = Str::limit((string) $request->userAgent(), 255, '');

        // The schema has no unique (event_id, phone) index, so serialize the
        // "already checked in?" check and the insert per event+phone. Without
        // this, two simultaneous requests can both pass the check and send two texts.
        try {
            $message = Cache::lock("checkin:{$event->id}:{$phone}", 10)->block(2, function () use ($data, $phone, $event, $agent, $consentText, $ip, $userAgent, $provider) {
                // Same phone, same event: no second visitor and no second text.
                $already = Visitor::query()
                    ->where('event_id', $event->id)
                    ->where('phone_e164', $phone)
                    ->exists();

                return $already ? null : $this->record($data, $phone, $event, $agent, $consentText, $ip, $userAgent, $provider);
            });
        } catch (LockTimeoutException) {
            // Another request for this same number is saving right now; it will record the visitor.
            return $thanks;
        }

        if ($message === null) {
            return $thanks;
        }

        // Runs in this request, after the response is sent: the visitor does not wait for Twilio.
        SendQueuedMessage::dispatchAfterResponse($message->id);

        return $thanks;
    }

    /**
     * Writes visitor, consent, short link and the queued message atomically.
     */
    private function record(array $data, string $phone, Event $event, Agent $agent, ConsentText $consentText, string|false $ip, string $userAgent, SmsProvider $provider): Message
    {
        return DB::transaction(function () use ($data, $phone, $event, $agent, $consentText, $ip, $userAgent, $provider) {
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
    }

    public function thanks(Event $event): View
    {
        abort_if($this->unavailable($event), 404);

        return view('checkin.thanks', ['event' => $event]);
    }
}
