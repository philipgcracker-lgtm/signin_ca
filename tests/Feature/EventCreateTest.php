<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCreateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'address_street' => '12 Maple Street',
            'city' => 'Welland',
            'postal_code' => 'l3c3j1',
            'event_date' => now('America/Toronto')->addDays(3)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'listing_url' => 'https://derekbreton.ca/listing/1',
            'price' => '559900',
            'bedrooms' => '3',
            'bathrooms' => '2.5',
            'blurb' => 'Nice home.',
        ], $over);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get('/events/create')->assertRedirect('/login');
        $this->post('/events', $this->payload())->assertRedirect('/login');
    }

    public function test_form_renders(): void
    {
        $this->actingAs(Agent::factory()->create())->get('/events/create')
            ->assertOk()->assertSee('New open house');
    }

    public function test_creates_event_owned_by_the_agent_with_slug_and_toronto_times(): void
    {
        $agent = Agent::factory()->create();
        $other = Agent::factory()->create();

        $res = $this->actingAs($agent)->post('/events', $this->payload(['agent_id' => $other->id, 'slug' => 'hacked', 'status' => 'live']));

        $event = Event::query()->firstOrFail();
        $res->assertRedirect(route('events.show', $event->id));
        $this->assertSame($agent->id, $event->agent_id);
        $this->assertNotSame('hacked', $event->slug);
        $this->assertStringStartsWith('12-maple-street-', $event->slug);
        $this->assertSame('upcoming', $event->status);
        $this->assertSame('L3C 3J1', $event->postal_code);
        $this->assertSame('14:00', $event->displayStart()->format('H:i'));
        $this->assertSame('16:00', $event->displayEnd()->format('H:i'));
    }

    public function test_slug_is_unique_per_event(): void
    {
        $agent = Agent::factory()->create();
        $this->actingAs($agent)->post('/events', $this->payload());
        $this->actingAs($agent)->post('/events', $this->payload());

        $slugs = Event::query()->pluck('slug');
        $this->assertCount(2, $slugs->unique());
    }

    public function test_the_new_events_qr_and_checkin_page_work(): void
    {
        ConsentText::factory()->create(['version' => 'v-test']);
        $agent = Agent::factory()->create();
        $this->actingAs($agent)->post('/events', $this->payload());
        $event = Event::query()->firstOrFail();

        $this->actingAs($agent)->get("/events/{$event->id}/qr.svg")->assertOk();
        $this->get("/e/{$event->slug}")->assertOk()->assertSee('12 Maple Street');
    }

    public function test_validation(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($agent)->post('/events', $this->payload([
            'address_street' => '',
            'postal_code' => 'nope',
            'event_date' => now('America/Toronto')->subDay()->format('Y-m-d'),
            'end_time' => '13:00',
            'listing_url' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['address_street', 'postal_code', 'event_date', 'end_time', 'listing_url']);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_listing_url_is_required(): void
    {
        $this->actingAs(Agent::factory()->create())->post('/events', $this->payload(['listing_url' => '']))
            ->assertSessionHasErrors('listing_url');
    }

    public function test_optional_fields_can_be_left_blank(): void
    {
        $this->actingAs(Agent::factory()->create())->post('/events', $this->payload([
            'postal_code' => '', 'price' => '', 'bedrooms' => '', 'bathrooms' => '', 'blurb' => '',
        ]))->assertSessionHasNoErrors();

        $event = Event::query()->firstOrFail();
        $this->assertNull($event->postal_code);
        $this->assertNull($event->price);
        $this->assertNull($event->blurb);
    }

    public function test_dashboard_links_to_the_form(): void
    {
        $this->actingAs(Agent::factory()->create())->get('/dashboard')
            ->assertOk()->assertSee(route('events.create'), false);
    }
}
