<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\MessageSender;
use Illuminate\Console\Command;

/**
 * PRD job `send_queued_messages`. Backstop for messages that were queued but
 * not sent (for example the PHP process died after the response). The normal
 * path is SendQueuedMessage::dispatchAfterResponse in the check-in request.
 */
class SendQueuedMessages extends Command
{
    protected $signature = 'messages:send-queued {--older-than=30 : only messages queued at least this many seconds ago}';

    protected $description = 'Send outbound messages that are still queued';

    public function handle(MessageSender $sender): int
    {
        $ids = Message::query()
            ->where('direction', 'out')
            ->where('status', 'queued')
            ->whereNull('updated_at')
            ->where('created_at', '<=', now()->subSeconds((int) $this->option('older-than')))
            ->orderBy('id')
            ->limit(50)
            ->pluck('id');

        foreach ($ids as $id) {
            $sender->send($id);
        }

        $this->info("Processed {$ids->count()} queued message(s).");

        return self::SUCCESS;
    }
}
