<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
| cPanel runs one cron entry every minute:
|   * * * * * cd /home/USER/scanin && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
|
| That is the only long-lived hook. No queue:work daemon is needed on shared
| hosting: the scheduler drains the database queue each minute and exits.
| ScanIn jobs (messages, event status, feedback, retention) are added here in
| later milestones.
*/
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();
