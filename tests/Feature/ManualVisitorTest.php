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

class ManualVisitorTest extends TestCase
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
        ]);
        // The online wording; the paper wording row comes from the migration.
        ConsentText::factory()->create(['version' => 'online-test']);
        $this->event = Event::factory()->create(['agent_id' => $this->agent->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Priya Sharma',
            'phone' => '(905) 555-0111',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get("/events/{$this->event->id}/visitors/create")->assertRedirect('/login');
        $this->post("/events/{$this->event->id}/visitors", $this->payload())->assertRedirect('/login');
        $this->assertSame(0, Visitor::count());
    }

    public function test_form_shows_the_paper_wording_for_the_agent(): void
    {
        $this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors/create")
            ->assertOk()
            ->assertSee('paper-v0-draft')
            ->assertSee('OK to text')
            ->assertSee('Derek Breton, Coldwell Banker Advantage Real Estate, Brokerage, with the listing link');
    }

    public function test_paper_wording_never_becomes_the_online_wording(): void
    {
        $this->assertNotNull(ConsentText::paper());
        $this->assertSame('online-test', ConsentText::current()->version);

        $this->get("/e/{$this->event->slug}")->assertOk()->assertDontSee('OK to text');
    }

    public function test_ticked_row_saves_a_paper_visitor_consent_and_queues_the_text(): void
    {
        $this->actingAs($this->agent)
            ->post("/events/{$this->event->id}/visitors", $this->payload([
                'email' => 'priya@example.com',
                'working_with_agent' => '0',
                'pre_approved' => '1',
                'buying_timeline' => '1_3_months',
                'sms_ok' => '1',
            ]))
            ->assertRedirect("/events/{$this->event->id}/visitors/create")
            ->assertSessionHas('status');

        $visitor = Visitor::sole();
        $this->assertSame('paper', $visitor->source);
        $this->assertSame('+19055550111', $visitor->phone_e164);
        $this->assertFalse($visitor->working_with_agent);
        $this->assertTrue($visitor->pre_approved);
        $this->assertNull($visitor->ip_address);
        $this->assertNull($visitor->user_agent);

        $consent = Consent::sole();
        $this->assertSame($visitor->id, $consent->visitor_id);
        $this->assertSame($this->agent->id, $consent->agent_id);
        $this->assertSame('paper-v0-draft', $consent->consentText->version);
        $this->assertSame('sms', $consent->channel);
        $this->assertSame('checkin', $consent->purpose);
        $this->assertTrue($consent->granted);
        $this->assertNull($consent->ip_address);

        $message = Message::sole();
        $this->assertSame($visitor->id, $message->visitor_id);
        $this->assertSame('checkin', $message->kind);
        $this->assertSame('+19055550111', $message->to_addr);
    }

    public function test_unticked_row_saves_the_visitor_but_sends_nothing_and_records_no_consent(): void
    {
        $this->actingAs($this->agent)
            ->post("/events/{$this->event->id}/visitors", $this->payload())
            ->assertRedirect();

        $this->assertSame('paper', Visitor::sole()->source);
        $this->assertSame(0, Consent::count());
        $this->assertSame(0, Message::count());
    }

    public function test_same_number_twice_is_refused_and_sends_one_text(): void
    {
        $this->actingAs($this->agent)->post("/events/{$this->event->id}/visitors", $this->payload(['sms_ok' => '1']));

        $this->actingAs($this->agent)
            ->post("/events/{$this->event->id}/visitors", $this->payload(['full_name' => 'Other Name', 'phone' => '905-555-0111', 'sms_ok' => '1']))
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Message::count());
    }

    public function test_bad_phone_and_missing_name_are_rejected(): void
    {
        $this->actingAs($this->agent)
            ->post("/events/{$this->event->id}/visitors", ['full_name' => '', 'phone' => '12345'])
            ->assertSessionHasErrors(['full_name', 'phone']);

        $this->assertSame(0, Visitor::count());
    }

    public function test_another_agents_event_is_a_404_for_both_form_and_save(): void
    {
        $other = Agent::factory()->create();
        $theirs = Event::factory()->create(['agent_id' => $other->id]);

        $this->actingAs($this->agent)->get("/events/{$theirs->id}/visitors/create")->assertNotFound();
        $this->actingAs($this->agent)->post("/events/{$theirs->id}/visitors", $this->payload(['sms_ok' => '1']))->assertNotFound();

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Message::count());
    }

    public function test_event_page_links_to_the_form_and_marks_paper_rows(): void
    {
        $this->actingAs($this->agent)->post("/events/{$this->event->id}/visitors", $this->payload());

        $this->actingAs($this->agent)->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertSee("/events/{$this->event->id}/visitors/create", false)
            ->assertSee('Priya Sharma')
            ->assertSee('Paper');
    }
}
