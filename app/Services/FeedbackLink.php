<?php

namespace App\Services;

use App\Models\ShortLink;
use App\Models\Visitor;
use Illuminate\Support\Facades\URL;

/**
 * The per-visitor feedback link. There is no token column in the PRD schema, so
 * the link is a signed, expiring URL (tamper-proof, no guessable ids), wrapped
 * in a short link so the text stays short and clicks are counted.
 */
final class FeedbackLink
{
    public const VALID_DAYS = 60;

    public static function signedUrl(Visitor $visitor): string
    {
        return URL::temporarySignedRoute('feedback.show', now()->addDays(self::VALID_DAYS), ['visitor' => $visitor->id]);
    }

    public static function shortUrl(Visitor $visitor): string
    {
        return ShortLink::createFor(self::signedUrl($visitor), $visitor->event_id, $visitor->id)->url();
    }
}
