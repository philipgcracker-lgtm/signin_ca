<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * One-off, idempotent loader for the Pilot 1 event (M1's event-creation UI is
 * skipped for the pilot). Safe to run repeatedly. It never creates an agent: the
 * agent must already exist, so no password ever passes through a script.
 *
 * Source of the event fields: Derek Breton's own open-house page, entered by
 * hand here. No listing data is fetched from anywhere (PRD: never MLS data).
 */
class SeedPilot extends Command
{
    protected $signature = 'scanin:seed-pilot {--agent= : email of the existing agent who owns the event}';

    protected $description = 'Seed the Pilot 1 open house (74 Pinehurst Drive, Welland) and draft consent wording';

    /** Placeholder wording. NOT legally reviewed. Replace via a new version before real visitors. */
    private const DRAFT_CONSENT = 'I agree to receive a text message from {agent_name} ({brokerage}) with a link to the listing I visited, and a follow-up text asking how I liked the open house. Message and data rates may apply. I can withdraw consent at any time by replying STOP. [DRAFT WORDING - NOT YET REVIEWED BY A LAWYER]';

    public function handle(): int
    {
        $email = (string) $this->option('agent');

        if ($email === '') {
            $this->error('Pass --agent=<email of an existing agent>.');

            return self::FAILURE;
        }

        $agent = Agent::query()->where('email', $email)->first();

        if ($agent === null) {
            $this->error("No agent with that email. Register the account first.");

            return self::FAILURE;
        }

        // Only fill blanks on the owner's profile; never overwrite what they entered.
        $filled = [];
        foreach (['brokerage_name' => 'Coldwell Banker Advantage Real Estate, Brokerage', 'phone' => '(905) 329-3472'] as $field => $value) {
            if (blank($agent->{$field})) {
                $agent->{$field} = $value;
                $filled[] = $field;
            }
        }
        if ($filled) {
            $agent->save();
            $this->line('Filled empty agent fields: '.implode(', ', $filled));
        }

        // Wording rows are immutable: create v0-draft once, never edit it.
        $consent = ConsentText::query()->firstOrCreate(
            ['version' => 'v0-draft'],
            ['body' => self::DRAFT_CONSENT, 'effective_from' => now()->subMinute()],
        );
        $this->line('Consent wording version: '.$consent->version.($consent->wasRecentlyCreated ? ' (created)' : ' (already present)'));

        // Event times are Toronto wall-clock; store them in the app timezone like every other timestamp.
        $tz = config('app.timezone');
        $start = Carbon::create(2026, 11, 15, 14, 0, 0, 'America/Toronto')->setTimezone($tz);
        $end = Carbon::create(2026, 11, 15, 16, 0, 0, 'America/Toronto')->setTimezone($tz);

        $event = Event::query()->withTrashed()->updateOrCreate(
            ['slug' => 'pinehurst-nov8'],
            [
                'agent_id' => $agent->id,
                'address_street' => '74 Pinehurst Drive',
                'city' => 'Welland',
                'province' => 'ON',
                'postal_code' => 'L3C 3J1',
                'price' => 559900,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'blurb' => 'Move-in ready 3 bed, 2 bath in North Welland. Derek is a contractor: ask him about the roof and the mechanicals.',
                'listing_url' => 'https://derekbreton.ca/listing/30294107',
                'event_start' => $start,
                'event_end' => $end,
                'status' => 'upcoming',
            ],
        );

        $this->info("Event ready: /e/{$event->slug}  ({$start->format('Y-m-d H:i')} to {$end->format('H:i')} {$tz}, agent #{$agent->id})");

        return self::SUCCESS;
    }
}
