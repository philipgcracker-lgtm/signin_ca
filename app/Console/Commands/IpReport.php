<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Console\Command;

/**
 * Diagnostic: shows which client IPs the app recorded for one event's check-ins,
 * masked (last IPv4 octet / IPv6 tail hidden). Used to confirm the server sees
 * each visitor's real IP and not a shared proxy address, since the per-IP rate
 * limit would otherwise treat a whole open house as one visitor. Read-only.
 */
class IpReport extends Command
{
    protected $signature = 'scanin:ip-report {slug}';

    protected $description = 'Show masked client IPs recorded for an event (checks the proxy setup)';

    public function handle(): int
    {
        $event = Event::query()->withTrashed()->where('slug', $this->argument('slug'))->first();

        if ($event === null) {
            $this->error('No event with that slug.');

            return self::FAILURE;
        }

        $rows = Visitor::query()->where('event_id', $event->id)->pluck('ip_address');
        $total = $rows->count();
        $masked = [];
        $private = 0;
        $missing = 0;

        foreach ($rows as $raw) {
            $ip = $raw === null ? false : @inet_ntop($raw);
            if ($ip === false) {
                $missing++;

                continue;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $private++;
            }
            $label = str_contains($ip, ':')
                ? implode(':', array_slice(explode(':', $ip), 0, 3)).':…'
                : preg_replace('/\.\d+$/', '.x', $ip);
            $masked[$label] = ($masked[$label] ?? 0) + 1;
        }

        arsort($masked);
        $this->line("Event {$event->slug}: {$total} check-in(s), ".count($masked).' distinct masked IP(s), '."{$missing} without an IP, {$private} private/reserved.");
        foreach ($masked as $label => $n) {
            $this->line("  {$label}  x{$n}");
        }

        if ($total >= 3 && count($masked) === 1) {
            $this->warn('All check-ins share one masked IP. If the visitors were on different networks, the server is seeing a proxy address; configure trusted proxies.');
        } elseif ($private > 0) {
            $this->warn('Private/reserved addresses recorded: the server is probably behind a proxy; configure trusted proxies.');
        } else {
            $this->info('Looks normal: distinct public addresses.');
        }

        return self::SUCCESS;
    }
}
