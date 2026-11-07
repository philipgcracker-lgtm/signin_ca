<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Message;
use App\Sms\SmsProvider;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends one queued message through the SmsProvider.
 *
 * Safe to call twice for the same message (after-response dispatch and the
 * scheduler sweep can race): the first caller claims the row by setting
 * updated_at atomically, everyone else returns without sending.
 *
 * Never throws: a provider failure is recorded on the row (status 'failed'
 * plus the error text), because a failed text must not break the visitor's
 * thank-you page.
 */
class MessageSender
{
    public function __construct(private readonly SmsProvider $provider) {}

    public function send(int $messageId): void
    {
        $claimed = Message::query()
            ->whereKey($messageId)
            ->where('status', 'queued')
            ->whereNull('updated_at')
            ->update(['updated_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $message = Message::query()->with('visitor')->findOrFail($messageId);

        if ($message->visitor?->sms_opt_out) {
            $this->finish($message, 'failed', error: 'Suppressed: visitor opted out.');

            return;
        }

        if ($message->event_id !== null && Event::query()->with('agent')->find($message->event_id)?->isSuspended()) {
            $this->finish($message, 'failed', error: 'Suppressed: account suspended.');

            return;
        }

        try {
            $result = $this->provider->send($message->to_addr, $message->body);

            $this->finish(
                $message,
                $result->status,
                $result->providerMessageId,
                $result->costCents,
            );
        } catch (Throwable $e) {
            report($e);

            $this->finish($message, 'failed', error: Str::limit($e->getMessage(), 500, ''));
        }
    }

    private function finish(Message $message, string $status, ?string $providerId = null, ?int $costCents = null, ?string $error = null): void
    {
        $message->forceFill([
            'status' => $status,
            'provider_message_id' => $providerId,
            'cost_cents' => $costCents,
            'error' => $error,
            'updated_at' => now(),
        ])->save();
    }
}
