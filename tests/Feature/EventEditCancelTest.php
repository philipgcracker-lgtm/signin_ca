<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventEditCancelTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'address_street' => '74 Pinehurst Drive',
            'city' => 'Welland',
            'postal_code' => 'l3c3j1',
            'event_date' => now('America/Toronto')->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'listing_url' => 'https://derekbreton.ca/listing/74',
            'price' => '559900',
            'bedrooms' => '3',
            'bathrooms' => '2.5',
            'blurb' => 'Nice home.',
        ], $over);
    }

    private function event(?Agent $agent = null, array $over = []): Event
    {
        return Event::factory()->create(array_merge(['agent_id' => ($agent ?? Agent::factory()->create())->id], $over));
    }

    public function test_guests_are_redirected(): void
    {
        $e = $this->event();

        $this->get("/events/{$e->id}/edit")->assertRedirect('/login');
        $this->patch("/events/{$e->id}", $this->payload())->assertRedirect('/login');
        $this->post("/events/{$e->id}/cancel")->assertRedirect('/login');
        $this->post("/events/{$e->id}/reopen")->assertRedirect('/login');
    }

    public function test_edit_form_is_prefilled_in_toronto_time(): void
    {
        $agent = Agent::factory()->create();
        $e = $this->event($agent, ['address_street' => '74 Pinehurst Drive', 'price' => 559900, 'bathrooms' => 2.5, 'postal_code' => 'L3C 3J1']);

        $this->actingAs($agent)->get("/events/{$e->id}/edit")->assertOk()
            ->assertSee('74 Pinehurst Drive')
            ->assertSee('value="559900"', false)
            ->assertSee('value="2.5"', false)
            ->assertSee('value="L3C 3J1"', false)
            ->assertSee($e->displayStart()->format('H:i'), false);
    }

    public function test_update_changes_details_but_never_the_slug_owner_or_status(): void
    {
        $agent = Agent::factory()->create();
        $other = Agent::factory()->create();
        $e = $this->event($agent, ['slug' => 'pinehurst-nov8']);
        $newDate = now('America/Toronto')->addDays(9)->format('Y-m-d');

        $this->actingAs($agent)
            ->patch("/events/{$e->id}", $this->payload([
                'event_date' => $newDate, 'start_time' => '13:00', 'end_time' => '15:30', 'city' => 'Fonthill',
                'agent_id' => $other->id, 'slug' => 'hacked', 'status' => 'cancelled',
            ]))
            ->assertRedirect(route('events.show', $e->id));

        $e->refresh();
        $this->assertSame('pinehurst-nov8', $e->slug);
        $this->assertSame($agent->id, $e->agent_id);
        $this->assertSame('live', $e->status);
        $this->assertSame('Fonthill', $e->city);
        $this->assertSame($newDate, $e->displayStart()->format('Y-m-d'));
        $this->assertSame('13:00', $e->displayStart()->format('H:i'));
        $this->assertSame('15:30', $e->displayEnd()->format('H:i'));
        $this->assertSame('L3C 3J1', $e->postal_code);
    }

    public function test_a_new_date_cannot_be_in_the_past_but_keeping_a_past_date_is_fine(): void
    {
        $agent = Agent::factory()->create();
        $past = now('America/Toronto')->subDays(5);
        $e = $this->event($agent, [
            'event_start' => $past->copy()->setTime(14, 0)->setTimezone('UTC'),
            'event_end' => $past->copy()->setTime(16, 0)->setTimezone('UTC'),
        ]);

        $this->actingAs($agent)
            ->patch("/events/{$e->id}", $this->payload(['event_date' => now('America/Toronto')->subDay()->format('Y-m-d')]))
            ->assertSessionHasErrors('event_date');

        $this->actingAs($agent)
            ->patch("/events/{$e->id}", $this->payload(['event_date' => $past->format('Y-m-d'), 'city' => 'Thorold']))
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('Thorold', $e->fresh()->city);
    }

    public function test_validation_still_applies_on_update(): void
    {
        $agent = Agent::factory()->create();
        $e = $this->event($agent);

        $this->actingAs($agent)->patch("/events/{$e->id}", $this->payload(['listing_url' => 'http://insecure.example', 'end_time' => '13:00']))
            ->assertSessionHasErrors(['listing_url', 'end_time']);
    }

    public function test_another_agents_event_is_a_404_for_edit_update_cancel_and_reopen(): void
    {
        $mine = Agent::factory()->create();
        $e = $this->event(null, ['city' => 'Original']);

        $this->actingAs($mine)->get("/events/{$e->id}/edit")->assertNotFound();
        $this->actingAs($mine)->patch("/events/{$e->id}", $this->payload(['city' => 'Hijacked']))->assertNotFound();
        $this->actingAs($mine)->post("/events/{$e->id}/cancel")->assertNotFound();
        $this->actingAs($mine)->post("/events/{$e->id}/reopen")->assertNotFound();

        $e->refresh();
        $this->assertSame('Original', $e->city);
        $this->assertSame('live', $e->status);
    }

    public function test_cancel_turns_off_the_check_in_page_keeps_the_data_and_reopen_restores_it(): void
    {
        $agent = Agent::factory()->create();
        $e = $this->event($agent);
        ConsentText::factory()->create();
        $e->visitors()->create(['full_name' => 'Pat Visitor', 'phone_e164' => '+19055550123']);

        $this->get("/e/{$e->slug}")->assertOk();

        $this->actingAs($agent)->post("/events/{$e->id}/cancel")->assertRedirect(route('events.show', $e->id));
        $this->assertSame('cancelled', $e->fresh()->status);
        $this->get("/e/{$e->slug}")->assertNotFound();

        $this->actingAs($agent)->get("/events/{$e->id}")->assertOk()
            ->assertSee('This open house is cancelled')->assertSee('Pat Visitor')->assertSee('Reopen');
        $this->assertSame(1, $e->visitors()->count());

        $this->actingAs($agent)->post("/events/{$e->id}/reopen")->assertRedirect(route('events.show', $e->id));
        $this->assertSame('live', $e->fresh()->status);
        $this->get("/e/{$e->slug}")->assertOk();
    }

    public function test_a_cancelled_event_cannot_be_edited_until_reopened(): void
    {
        $agent = Agent::factory()->create();
        $e = $this->event($agent, ['status' => 'cancelled', 'city' => 'Original']);

        $this->actingAs($agent)->get("/events/{$e->id}/edit")->assertRedirect(route('events.show', $e->id));
        $this->actingAs($agent)->patch("/events/{$e->id}", $this->payload(['city' => 'Changed']))->assertRedirect(route('events.show', $e->id));

        $this->assertSame('Original', $e->fresh()->city);
    }

    public function test_the_show_page_offers_edit_and_the_edit_page_offers_cancel(): void
    {
        $agent = Agent::factory()->create();
        $e = $this->event($agent);

        $this->actingAs($agent)->get("/events/{$e->id}")->assertOk()->assertSee(route('events.edit', $e->id), false);
        $this->actingAs($agent)->get("/events/{$e->id}/edit")->assertOk()->assertSee(route('events.cancel', $e->id), false);
    }

    public function test_a_suspended_agent_cannot_edit_or_cancel(): void
    {
        $agent = Agent::factory()->suspended()->create();
        $e = $this->event($agent, ['city' => 'Original']);

        $this->actingAs($agent)->get("/events/{$e->id}/edit")->assertRedirect(route('dashboard'));
        $this->actingAs($agent)->patch("/events/{$e->id}", $this->payload(['city' => 'Changed']))->assertRedirect(route('dashboard'));
        $this->actingAs($agent)->post("/events/{$e->id}/cancel")->assertRedirect(route('dashboard'));

        $e->refresh();
        $this->assertSame('Original', $e->city);
        $this->assertSame('live', $e->status);
    }
}
