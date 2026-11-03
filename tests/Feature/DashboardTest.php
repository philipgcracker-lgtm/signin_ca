<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function visitor(Event $event, array $attrs = []): Visitor
    {
        $visitor = new Visitor;
        $visitor->forceFill(array_merge([
            'event_id' => $event->id,
            'full_name' => 'Test Visitor',
            'phone_e164' => '+19055550123',
        ], $attrs))->save();

        return $visitor;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $event = Event::factory()->create();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/events/'.$event->id)->assertRedirect('/login');
    }

    public function test_dashboard_lists_only_my_events(): void
    {
        $me = Agent::factory()->create();
        $other = Agent::factory()->create();
        Event::factory()->create(['agent_id' => $me->id, 'address_street' => '1 Mine Street']);
        Event::factory()->create(['agent_id' => $other->id, 'address_street' => '2 Theirs Street']);

        $this->actingAs($me)->get('/dashboard')
            ->assertOk()
            ->assertSee('1 Mine Street')
            ->assertDontSee('2 Theirs Street');
    }

    public function test_event_page_shows_visitors_with_consent_and_text_status(): void
    {
        $me = Agent::factory()->create();
        $event = Event::factory()->create(['agent_id' => $me->id]);
        $text = ConsentText::factory()->create();

        $ok = $this->visitor($event, [
            'full_name' => 'Priya Sharma',
            'phone_e164' => '+19055550111',
            'pre_approved' => true,
            'working_with_agent' => false,
            'buying_timeline' => '1_3_months',
        ]);
        Consent::create([
            'visitor_id' => $ok->id, 'agent_id' => $me->id, 'consent_text_id' => $text->id,
            'channel' => 'sms', 'granted' => true,
        ]);
        Message::create([
            'visitor_id' => $ok->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms',
            'kind' => 'checkin', 'to_addr' => '+19055550111', 'from_addr' => '+17372508034',
            'body' => 'x', 'provider' => 'twilio', 'status' => 'delivered',
        ]);

        $bad = $this->visitor($event, ['full_name' => 'Marcus Tran', 'phone_e164' => '+19055550222']);
        Message::create([
            'visitor_id' => $bad->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms',
            'kind' => 'checkin', 'to_addr' => '+19055550222', 'from_addr' => '+17372508034',
            'body' => 'x', 'provider' => 'twilio', 'status' => 'failed', 'error' => 'Unverified number',
        ]);

        $this->actingAs($me)->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Priya Sharma')
            ->assertSee('(905) 555-0111')
            ->assertSee('1–3 months')
            ->assertSee('Agreed')
            ->assertSee('Delivered')
            ->assertSee('Marcus Tran')
            ->assertSee('Failed')
            ->assertSee('Unverified number');
    }

    public function test_cannot_view_another_agents_event(): void
    {
        $me = Agent::factory()->create();
        $other = Agent::factory()->create();
        $event = Event::factory()->create(['agent_id' => $other->id]);
        $this->visitor($event, ['full_name' => 'Secret Person']);

        $this->actingAs($me)->get('/events/'.$event->id)->assertNotFound();
    }

    public function test_anonymized_visitor_shows_no_personal_details(): void
    {
        $me = Agent::factory()->create();
        $event = Event::factory()->create(['agent_id' => $me->id]);
        $this->visitor($event, ['full_name' => '', 'phone_e164' => '', 'anonymized_at' => now()]);

        $this->actingAs($me)->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Personal details removed');
    }

    public function test_empty_event_says_so(): void
    {
        $me = Agent::factory()->create();
        $event = Event::factory()->create(['agent_id' => $me->id]);

        $this->actingAs($me)->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('No one has checked in yet.');
    }
}
