<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Event;

/**
 * Message 1 (sent on check-in). PRD wording, plus the sender identification the
 * compliance section requires (agent name and brokerage).
 *
 * The link is followed by a space, not a period, because some phones include a
 * trailing period in the tappable URL.
 */
final class CheckinMessage
{
    public const SINGLE_SEGMENT = 160;

    public static function build(Event $event, Agent $agent, string $link): string
    {
        $sender = $agent->full_name.(filled($agent->brokerage_name) ? ', '.$agent->brokerage_name : '');

        $make = fn (string $address) => "Thanks for visiting {$address}. Listing details: {$link} Reply STOP to opt out. -{$sender}";

        $withCity = $make($event->shortAddress());

        if (mb_strlen($withCity) <= self::SINGLE_SEGMENT) {
            return $withCity;
        }

        // Drop the city first. Never drop the sender identification: if it is
        // still long, the message goes out as two segments instead.
        return $make($event->address_street);
    }
}
