<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventQrTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = Agent::factory()->create([
            'full_name' => 'Derek Breton',
            'brokerage_name' => 'Coldwell Banker Advantage Real Estate, Brokerage',
            'phone' => '(905) 329-3472',
        ]);
        $this->event = Event::factory()->create(['agent_id' => $this->agent->id, 'slug' => 'pinehurst-nov8']);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get("/events/{$this->event->id}/qr.svg")->assertRedirect('/login');
        $this->get("/events/{$this->event->id}/sign")->assertRedirect('/login');
    }

    public function test_another_agents_event_is_a_404(): void
    {
        $other = Agent::factory()->create();

        $this->actingAs($other)->get("/events/{$this->event->id}/qr.svg")->assertNotFound();
        $this->actingAs($other)->get("/events/{$this->event->id}/sign")->assertNotFound();
    }

    public function test_svg_is_served_and_download_sets_a_filename(): void
    {
        $inline = $this->actingAs($this->agent)->get("/events/{$this->event->id}/qr.svg");
        $inline->assertOk()->assertHeader('content-type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $inline->getContent());
        $this->assertNull($inline->headers->get('content-disposition'));

        $this->actingAs($this->agent)->get("/events/{$this->event->id}/qr.svg?download=1")
            ->assertHeader('content-disposition', 'attachment; filename="scanin-pinehurst-nov8-qr.svg"');
    }

    public function test_sign_shows_event_agent_and_the_checkin_address(): void
    {
        $this->actingAs($this->agent)->get("/events/{$this->event->id}/sign")
            ->assertOk()
            ->assertSee($this->event->address_street)
            ->assertSee('Scan to check in')
            ->assertSee('Derek Breton')
            ->assertSee('Coldwell Banker Advantage Real Estate, Brokerage')
            ->assertSee(route('checkin.show', 'pinehurst-nov8'))
            ->assertSee('<svg', false)
            ->assertDontSee('/build/assets', false);
    }

    public function test_event_page_links_to_sign_and_qr(): void
    {
        $this->actingAs($this->agent)->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertSee("/events/{$this->event->id}/sign", false)
            ->assertSee("/events/{$this->event->id}/qr.svg", false);
    }
}
