<?php

namespace App\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default driver. Sends nothing: writes the message to the Laravel log with the
 * phone number masked. Safe for local work, tests and the first server deploy.
 */
class LogSmsProvider implements SmsProvider
{
    public function name(): string
    {
        return 'log';
    }

    public function from(): string
    {
        return 'log';
    }

    public function send(string $toE164, string $body): SmsResult
    {
        $masked = substr($toE164, 0, 2).str_repeat('*', max(0, strlen($toE164) - 6)).substr($toE164, -4);

        Log::info("[sms:log] to {$masked}: {$body}");

        return new SmsResult('sent', 'log-'.Str::uuid());
    }
}
