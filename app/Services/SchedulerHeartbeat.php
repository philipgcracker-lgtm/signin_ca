<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Proof that the host's every-minute cron is really running schedule:run.
 * The scheduler stamps the time each minute; the admin overview reads it.
 */
class SchedulerHeartbeat
{
    private const KEY = 'scheduler:heartbeat';

    /** Longer than this without a beat and the scheduler is treated as down. */
    public const STALE_AFTER_MINUTES = 3;

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->getTimestamp());
    }

    public static function lastBeat(): ?Carbon
    {
        $ts = Cache::get(self::KEY);

        return is_numeric($ts) ? Carbon::createFromTimestamp((int) $ts) : null;
    }

    public static function isRunning(): bool
    {
        $last = self::lastBeat();

        return $last !== null && $last->greaterThan(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }
}
