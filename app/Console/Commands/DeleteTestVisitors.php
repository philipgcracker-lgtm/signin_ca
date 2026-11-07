<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes test check-ins from one event, including their consent rows, short
 * links and messages. Guarded: it deletes only when the event has exactly
 * --expect visitors, so it can never sweep up real check-ins by accident.
 * Prints masked rows (name, last 4 digits) before deleting.
 */
class DeleteTestVisitors extends Command
{
    protected $signature = 'scanin:delete-test-visitors {slug} {--expect= : exact number of visitors the event must have}';

    protected $description = 'Delete test visitors from an event (only if the visitor count matches --expect)';

    public function handle(): int
    {
        $event = Event::query()->where('slug', $this->argument('slug'))->first();

        if ($event === null) {
            $this->error('No event with that slug.');

            return self::FAILURE;
        }

        if ($this->option('expect') === null || ! ctype_digit((string) $this->option('expect'))) {
            $this->error('Pass --expect=<number of visitors the event should have>.');

            return self::FAILURE;
        }

        $visitors = Visitor::query()->where('event_id', $event->id)->orderBy('id')->get();
        $expect = (int) $this->option('expect');

        foreach ($visitors as $v) {
            $this->line(sprintf('  #%d  %s  ...%s  %s', $v->id, $v->full_name, substr((string) $v->phone_e164, -4), $v->created_at));
        }

        if ($visitors->count() !== $expect) {
            $this->error("Event has {$visitors->count()} visitors, expected {$expect}. Nothing deleted.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($visitors) {
            $ids = $visitors->pluck('id');
            // Messages keep the phone number and are only detached (not deleted) by the FK, so remove them explicitly.
            Message::query()->whereIn('visitor_id', $ids)->delete();
            Visitor::query()->whereIn('id', $ids)->delete(); // consents and short links cascade
        });

        $this->info("Deleted {$visitors->count()} visitors from {$event->slug}.");

        return self::SUCCESS;
    }
}
