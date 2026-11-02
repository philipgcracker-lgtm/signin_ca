<?php

namespace App\Sms;

/**
 * Outbound SMS abstraction. M2 needs send() only; handleInbound() and status()
 * (STOP handling, delivery receipts) arrive with M3.
 */
interface SmsProvider
{
    /** Short provider name stored in messages.provider (max 20 chars). */
    public function name(): string;

    /** The sender address stored in messages.from_addr. */
    public function from(): string;

    /**
     * @throws SmsException when the provider rejects or cannot accept the message
     */
    public function send(string $toE164, string $body): SmsResult;
}
