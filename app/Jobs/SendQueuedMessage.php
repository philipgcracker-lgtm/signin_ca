<?php

namespace App\Jobs;

use App\Services\MessageSender;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Not queued on purpose: it runs with ::dispatchAfterResponse(), in the same
 * PHP request, right after the visitor's response is sent. The cron-driven
 * database queue only wakes once a minute, which would miss the 30-second
 * delivery target. The scheduler sweep (messages:send-queued) is the backstop.
 */
class SendQueuedMessage
{
    use Dispatchable;

    public function __construct(public readonly int $messageId) {}

    public function handle(MessageSender $sender): void
    {
        $sender->send($this->messageId);
    }
}
