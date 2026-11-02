<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Defaults to an event that is live right now (started 1 h ago, ends in 1 h).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'slug' => Str::lower(Str::random(10)),
            'address_street' => '74 Pinehurst Drive',
            'city' => 'Welland',
            'province' => 'ON',
            'postal_code' => 'L3C 3J1',
            'price' => 559900,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'blurb' => 'Move-in ready.',
            'listing_url' => 'https://example.com/listing/1',
            'event_start' => now()->subHour(),
            'event_end' => now()->addHour(),
            'status' => 'live',
        ];
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'event_start' => now()->subHours(4),
            'event_end' => now()->subHours(2),
            'status' => 'ended',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
