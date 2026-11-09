<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * PRD job `retention_sweep`. Anonymizes visitors whose event ended more than the
 * owning agent's `retention_months` ago (never less than MIN_MONTHS), using the
 * same erasure as a DELETE reply. The clock runs from the event's end. Exporting
 * a list does not extend retention. Consent records stay (proof of consent).
 * Logs counts only, never personal details.
 */
class RetentionSweep extends Command
{
    public const MIN_MONTHS = 12;

    public const DEFAULT_MONTHS = 24;

    protected $signature = 'visitors:retention-sweep {--dry-run : count what would be anonymized, change nothing}';

    protected $description = 'Anonymize visitors whose retention period has passed';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $events = 0;
        $visitors = 0;

        // Soft-deleted events and agents still hold visitor rows, so include them.
        Event::withTrashed()
            ->where('event_end', '<', now()->subMonths(self::MIN_MONTHS))
            ->whereHas('visitors', fn ($q) => $q->whereNull('anonymized_at'))
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($dry, &$events, &$visitors) {
                foreach ($chunk as $event) {
                    $months = Agent::withTrashed()->whereKey($event->agent_id)->value('retention_months') ?? self::DEFAULT_MONTHS;
                    $months = max(self::MIN_MONTHS, (int) $months);

                    if ($event->event_end->greaterThanOrEqualTo(now()->subMonths($months))) {
                        continue;
                    }

                    $due = Visitor::query()->where('event_id', $event->id)->whereNull('anonymized_at');
                    $count = (clone $due)->count();
                    $events++;
                    $visitors += $count;

                    if (! $dry) {
                        $due->orderBy('id')->each(fn (Visitor $v) => $v->anonymize());
                    }
                }
            });

        $verb = $dry ? 'would anonymize' : 'anonymized';
        $line = "Retention sweep: {$verb} {$visitors} visitor(s) across {$events} event(s).";
        $this->info($line);

        if (! $dry && $visitors > 0) {
            Log::warning($line);
        }

        return self::SUCCESS;
    }
}
