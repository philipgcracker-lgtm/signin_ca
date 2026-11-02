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
        // Check-in abuse limits (PRD: per IP and per event, no CAPTCHA).
        RateLimiter::for('checkin', function (Request $request) {
            // Throttle runs before route-model binding, so `event` may still be the raw slug.
            $event = $request->route('event');
            $eventKey = is_object($event) ? $event->getRouteKey() : (string) $event;

            return [
                Limit::perMinute(8)->by('ip:'.$request->ip()),
                Limit::perHour(300)->by('event:'.$eventKey),
            ];
        });
    }
}
