<?php

namespace App\Sms;

final class SmsResult
{
    /**
     * @param  string  $status  one of the messages.status values: 'sent' (accepted by the provider) or 'failed'
     */
    public function __construct(
        public readonly string $status = 'sent',
        public readonly ?string $providerMessageId = null,
        public readonly ?int $costCents = null,
    ) {}
}
