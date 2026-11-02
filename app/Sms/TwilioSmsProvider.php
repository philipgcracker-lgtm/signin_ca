<?php

namespace App\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Twilio Programmable Messaging over its REST API (no SDK needed).
 * Credentials come from config('services.twilio'); they are never logged.
 * Delivery receipts and inbound STOP handling are M3.
 */
class TwilioSmsProvider implements SmsProvider
{
    public function __construct(
        private readonly ?string $sid,
        private readonly ?string $token,
        private readonly ?string $from,
    ) {}

    public function name(): string
    {
        return 'twilio';
    }

    public function from(): string
    {
        return (string) $this->from;
    }

    public function send(string $toE164, string $body): SmsResult
    {
        if (blank($this->sid) || blank($this->token) || blank($this->from)) {
            throw new SmsException('Twilio is not configured (TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM).');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->sid, $this->token)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                    'To' => $toE164,
                    'From' => $this->from,
                    'Body' => $body,
                ]);
        } catch (ConnectionException) {
            throw new SmsException('Could not reach Twilio (network or timeout).');
        }

        if ($response->failed()) {
            $code = $response->json('code');
            $detail = $response->json('message') ?? 'unknown error';

            throw new SmsException('Twilio HTTP '.$response->status().($code ? " (code {$code})" : '').': '.$detail);
        }

        $status = (string) $response->json('status');

        return new SmsResult(
            in_array($status, ['failed', 'undelivered'], true) ? 'failed' : 'sent',
            $response->json('sid'),
        );
    }
}
