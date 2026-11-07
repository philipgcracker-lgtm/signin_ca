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
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
