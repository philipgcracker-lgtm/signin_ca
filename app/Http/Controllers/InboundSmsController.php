<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Inbound SMS webhook (Twilio "A message comes in").
 *
 * - STOP-type keywords opt the phone out everywhere (every visitor row with that
 *   number, across all events and agents) and revoke the SMS consents.
 * - DELETE opts out AND anonymizes those visitors (PRD: deletion on request).
 * - Anything else is recorded and ignored. START/UNSTOP never re-enable texting:
 *   a new opt-in has to come from a fresh check-in with the consent box ticked.
 *
 * Only Twilio can call this: the request signature is verified with the auth
 * token, and the route fails closed if no token is configured.
 */
class InboundSmsController extends Controller
{
    private const STOP_WORDS = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT', 'REVOKE', 'OPTOUT', 'OPT OUT'];

    private const DELETE_WORDS = ['DELETE', 'DELETE MY DATA'];

    private const HELP_WORDS = ['HELP', 'INFO'];

    public function twilio(Request $request): Response
    {
        abort_unless($this->validSignature($request), 403);

        $from = trim((string) $request->input('From'));
        $keyword = $this->keyword((string) $request->input('Body'));
        $isDelete = in_array($keyword, self::DELETE_WORDS, true);
        $isStop = $isDelete
            || in_array($keyword, self::STOP_WORDS, true)
            || strtoupper((string) $request->input('OptOutType')) === 'STOP';

        $isHelp = in_array($keyword, self::HELP_WORDS, true);

        $visitors = $from === '' ? collect() : Visitor::query()
            ->where('phone_e164', $from)
            ->whereNull('anonymized_at')
            ->orderByDesc('id')
            ->get();

        // Unknown number: nothing of ours to touch, and we do not store strangers' texts.
        // Anyone who asks for HELP still gets an answer, though.
        if ($visitors->isEmpty()) {
            return $this->twiml($isHelp ? $this->helpText() : null);
        }

        $this->record($request, $visitors->first(), $from, $isStop ? ($isDelete ? 'DELETE' : 'STOP') : null);

        if ($isStop) {
            foreach ($visitors as $visitor) {
                $visitor->forceFill(['sms_opt_out' => true])->save();
                $visitor->consents()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
        }

        if ($isDelete) {
            foreach ($visitors as $visitor) {
                $visitor->anonymize();
            }

            return $this->twiml('Your information has been deleted from ScanIn and you will not receive more messages. Reply to this number if you need anything else.');
        }

        return $this->twiml($isHelp ? $this->helpText() : null);
    }

    /** Who is texting, how to stop, how to erase, where the policy is. Kept to one SMS segment. */
    private function helpText(): string
    {
        $host = preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/'));

        return "ScanIn: open-house texts sent for your listing agent. Reply STOP to opt out, DELETE to erase your info. Msg&data rates may apply. {$host}/privacy";
    }

    /** Upper-case, letters and single spaces only: " Stop. " => "STOP". */
    private function keyword(string $body): string
    {
        $clean = preg_replace('/[^\p{L}\s]/u', '', $body) ?? '';

        return trim(preg_replace('/\s+/', ' ', Str::upper($clean)) ?? '');
    }

    /** One row per Twilio MessageSid, so a webhook retry does not duplicate it. */
    private function record(Request $request, Visitor $visitor, string $from, ?string $keyword): void
    {
        $sid = (string) $request->input('MessageSid');

        if ($sid !== '' && Message::query()->where('provider', 'twilio')->where('direction', 'in')->where('provider_message_id', $sid)->exists()) {
            return;
        }

        Message::create([
            'visitor_id' => $visitor->id,
            'event_id' => $visitor->event_id,
            'direction' => 'in',
            'channel' => 'sms',
            'kind' => 'inbound',
            'to_addr' => Str::limit((string) $request->input('To'), 250, ''),
            'from_addr' => $from,
            'body' => $keyword ?? Str::limit((string) $request->input('Body'), 300, ''),
            'provider' => 'twilio',
            'provider_message_id' => $sid !== '' ? Str::limit($sid, 64, '') : null,
            'status' => 'received',
        ]);
    }

    /**
     * Twilio signature: base64(HMAC-SHA1(token, url . each POST param sorted by
     * name as name.value)). The URL is built from APP_URL, not from the request,
     * because behind the host's proxy the request may look like plain http.
     */
    private function validSignature(Request $request): bool
    {
        $token = (string) config('services.twilio.token');
        $given = (string) $request->header('X-Twilio-Signature');

        if ($token === '' || $given === '') {
            return false;
        }

        $params = $request->post();
        ksort($params);
        $data = rtrim((string) config('app.url'), '/').'/webhooks/sms/twilio';

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                return false;
            }
            $data .= $key.$value;
        }

        return hash_equals(base64_encode(hash_hmac('sha1', $data, $token, true)), $given);
    }

    private function twiml(?string $reply = null): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Response>'
            .($reply === null ? '' : '<Message>'.e($reply).'</Message>')
            .'</Response>';

        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
