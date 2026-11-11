<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Provider webhooks are authenticated by signature, not by a browser CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        // Twilio signs the exact bytes it sent, so the webhook body must reach the signature
        // check untouched (no trimming, no ""->null). A reply like "STOP\n" would fail otherwise.
        $middleware->trimStrings(except: [fn (\Illuminate\Http\Request $request) => $request->is('webhooks/*')]);
        $middleware->convertEmptyStringsToNull(except: [fn (\Illuminate\Http\Request $request) => $request->is('webhooks/*')]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A visitor whose check-in is refused (stale form = 419, too many requests = 429) must still
        // get a readable page with the listing link, not a bare framework error.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            $reasons = [
                419 => 'This page was open too long, or your browser blocked cookies.',
                429 => 'Too many check-ins came from this connection just now.',
            ];

            if (! isset($reasons[$e->getStatusCode()]) || ! $request->routeIs('checkin.store')) {
                return null;
            }

            $event = $request->route('event');
            $event = $event instanceof \App\Models\Event ? $event : \App\Models\Event::query()->where('slug', (string) $event)->first();

            if ($event === null || $event->isCancelled() || $event->isSuspended() || $event->agent === null) {
                return null;
            }

            return response()->view('checkin.retry', ['event' => $event, 'reason' => $reasons[$e->getStatusCode()]], $e->getStatusCode());
        });
    })->create();
