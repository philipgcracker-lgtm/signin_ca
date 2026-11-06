<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAbuseGuardTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'address_street' => '12 Maple Street',
            'city' => 'Welland',
            'event_date' => now('America/Toronto')->addDays(3)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'listing_url' => 'https://derekbreton.ca/listing/1',
        ], $over);
    }

    public function test_registration_is_closed_by_default(): void
    {
        $this->assertFalse(config('scanin.registration_open'));
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'full_name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();
        $this->assertSame(0, Agent::count());
    }

    public function test_new_registrations_are_unapproved_when_open(): void
    {
        config(['scanin.registration_open' => true]);

        $this->post('/register', [
            'full_name' => 'New Agent', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertFalse(Agent::where('email', 'new@example.com')->firstOrFail()->isApproved());
    }

    public function test_unapproved_agent_cannot_create_events(): void
    {
        $agent = Agent::factory()->unapproved()->create();

        $this->actingAs($agent)->get('/events/create')->assertRedirect(route('dashboard'));
        $this->actingAs($agent)->post('/events', $this->payload())->assertRedirect(route('dashboard'));
        $this->assertSame(0, Event::count());

        $this->actingAs($agent)->get('/dashboard')->assertOk()->assertSee('waiting for approval');
    }

    public function test_agent_cannot_approve_themselves_through_the_profile_form(): void
    {
        $agent = Agent::factory()->unapproved()->create();

        $this->actingAs($agent)->patch('/profile', [
            'full_name' => $agent->full_name, 'email' => $agent->email, 'approved_at' => now()->toDateTimeString(),
        ]);

        $this->assertFalse($agent->fresh()->isApproved());
    }

    public function test_approve_command_approves_and_revokes(): void
    {
        $agent = Agent::factory()->unapproved()->create(['email' => 'derek@example.com']);

        $this->artisan('scanin:approve-agent', ['email' => 'Derek@Example.com'])->assertSuccessful();
        $this->assertTrue($agent->fresh()->isApproved());

        $this->artisan('scanin:approve-agent', ['email' => 'derek@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($agent->fresh()->isApproved());

        $this->artisan('scanin:approve-agent', ['email' => 'nobody@example.com'])->assertFailed();
    }

    /**
     * @dataProvider badLinks
     */
    public function test_unsafe_listing_links_are_rejected(string $url): void
    {
        config(['app.url' => 'https://scanin.ca']);

        $this->actingAs(Agent::factory()->create())->post('/events', $this->payload(['listing_url' => $url]))
            ->assertSessionHasErrors('listing_url');

        $this->assertSame(0, Event::count());
    }

    public static function badLinks(): array
    {
        return [
            'http' => ['http://derekbreton.ca/x'],
            'javascript' => ['javascript:alert(1)'],
            'ip address' => ['https://192.168.1.5/x'],
            'localhost' => ['https://localhost/x'],
            'userinfo' => ['https://derekbreton.ca@evil.example/x'],
            'own domain' => ['https://scanin.ca/e/other'],
            'own subdomain' => ['https://login.scanin.ca/x'],
        ];
    }

    public function test_a_normal_https_listing_link_is_accepted(): void
    {
        config(['app.url' => 'https://scanin.ca']);

        $this->actingAs(Agent::factory()->create())->post('/events', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Event::count());
    }
}
