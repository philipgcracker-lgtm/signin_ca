<?php

namespace App\Providers;

use App\Sms\LogSmsProvider;
use App\Sms\SmsProvider;
use App\Sms\TwilioSmsProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // SMS_DRIVER=log (default, sends nothing) or twilio.
        $this->app->bind(SmsProvider::class, function () {
            return match (config('services.sms.driver')) {
                'twilio' => new TwilioSmsProvider(
                    config('services.twilio.sid'),
                    config('services.twilio.token'),
                    config('services.twilio.from'),
                ),
                default => new LogSmsProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Check-in abuse limit per IP (PRD: per IP and per event, no CAPTCHA).
        // The per-event limit lives in CheckinController::store(), keyed on the
        // resolved event id: a key built from the URL slug could be bypassed
        // with case or trailing-space variants that MySQL treats as equal.
        RateLimiter::for('checkin', function (Request $request) {
            return Limit::perMinute(8)->by('ip:'.$request->ip());
        });
    }
}
