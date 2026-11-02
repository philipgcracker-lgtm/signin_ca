<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CheckinTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    private ConsentText $consent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = Agent::factory()->create([
            'full_name' => 'Derek Breton',
            'brokerage_name' => 'Coldwell Banker Advantage',
        ]);
        $this->consent = ConsentText::factory()->create(['version' => 'v-test']);
        $this->event = Event::factory()->create([
            'agent_id' => $this->agent->id,
            'slug' => 'pinehurst',
            'listing_url' => 'https://derekbreton.ca/listing/30294107',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Pat Visitor',
            'phone' => '(613) 555-0123',
            'consent' => '1',
            'consent_text_id' => $this->consent->id,
        ], $overrides);
    }

    public function test_checkin_page_renders_event_agent_and_versioned_consent(): void
    {
        $response = $this->get('/e/pinehurst');

        $response->assertOk()
            ->assertSee('74 Pinehurst Drive')
            ->assertSee('$559,900')
            ->assertSee('Derek Breton')
            ->assertSee('I agree to receive a text from Derek Breton (Coldwell Banker Advantage).')
            ->assertSee('name="consent"', false)
            ->assertDontSee('name="consent" value="1" checked', false);
    }

    public function test_event_times_are_shown_in_toronto_time_whatever_the_app_timezone(): void
    {
        // Stored as UTC (the production app timezone): 2:00 PM to 4:00 PM Toronto on Nov 8 2026 (EST, UTC-5).
        $this->event->update([
            'event_start' => '2026-11-08 19:00:00',
            'event_end' => '2026-11-08 21:00:00',
        ]);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-08 18:00:00', 'UTC'));

        $this->get('/e/pinehurst')
            ->assertOk()
            ->assertSee('Sun, Nov 8, 2:00&ndash;4:00 PM', false)
            ->assertDontSee('7:00');
    }

    public function test_checkin_page_is_plain_html_under_100kb_with_no_vite_assets(): void
    {
        $response = $this->get('/e/pinehurst');

        $response->assertOk()->assertDontSee('/build/assets', false)->assertDontSee('<script', false);
        $this->assertLessThan(100 * 1024, strlen($response->getContent()));
    }

    public function test_ended_event_redirects_to_the_listing_with_no_form(): void
    {
        $this->event->update(['event_start' => now()->subHours(4), 'event_end' => now()->subHour()]);

        $this->get('/e/pinehurst')->assertRedirect('https://derekbreton.ca/listing/30294107');
    }

    public function test_cancelled_and_unknown_events_are_404(): void
    {
        $this->event->update(['status' => 'cancelled']);

        $this->get('/e/pinehurst')->assertNotFound();
        $this->get('/e/does-not-exist')->assertNotFound();
    }

    public function test_page_is_unavailable_without_consent_wording(): void
    {
        ConsentText::query()->delete();

        $this->get('/e/pinehurst')->assertStatus(503);
    }

    public function test_checkin_records_visitor_consent_message_and_short_link(): void
    {
        Log::spy();

        $this->post('/e/pinehurst/checkin', $this->payload([
            'email' => 'pat@example.com',
            'buying_timeline' => '1_3_months',
            'working_with_agent' => '0',
            'pre_approved' => '1',
        ], ))->assertRedirect('/e/pinehurst/thanks');

        $visitor = Visitor::sole();
        $this->assertSame('+16135550123', $visitor->phone_e164);
        $this->assertSame($this->event->id, $visitor->event_id);
        $this->assertSame('1_3_months', $visitor->buying_timeline);
        $this->assertFalse($visitor->working_with_agent);
        $this->assertTrue($visitor->pre_approved);
        $this->assertNotNull($visitor->created_at);

        $consent = Consent::sole();
        $this->assertSame($visitor->id, $consent->visitor_id);
        $this->assertSame($this->agent->id, $consent->agent_id);
        $this->assertSame($this->consent->id, $consent->consent_text_id);
        $this->assertSame('sms', $consent->channel);
        $this->assertTrue($consent->granted);
        $this->assertNotNull($consent->user_agent);

        $link = ShortLink::sole();
        $this->assertSame('https://derekbreton.ca/listing/30294107', $link->target_url);
        $this->assertSame($visitor->id, $link->visitor_id);
        $this->assertSame(6, strlen($link->code));

        // dispatchAfterResponse runs at terminate; the log driver "sends" it.
        $message = Message::sole();
        $this->assertSame('sent', $message->status);
        $this->assertSame('log', $message->provider);
        $this->assertSame('+16135550123', $message->to_addr);
        $this->assertSame('checkin', $message->kind);
        $this->assertStringContainsString('74 Pinehurst Drive, Welland', $message->body);
        $this->assertStringContainsString($link->url(), $message->body);
        $this->assertStringContainsString('Reply STOP to opt out', $message->body);
        $this->assertStringContainsString('-Derek Breton, Coldwell Banker Advantage', $message->body);
    }

    public function test_message_body_stays_one_segment_for_a_normal_agent(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload());

        $this->assertLessThanOrEqual(160, mb_strlen(Message::sole()->body));
    }

    public function test_consent_is_required(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload(['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Message::count());
    }

    public function test_invalid_phone_numbers_are_rejected(): void
    {
        foreach (['12345', '+44 20 7946 0958', '(113) 555-0123', 'not a number'] as $bad) {
            $this->post('/e/pinehurst/checkin', $this->payload(['phone' => $bad]))
                ->assertSessionHasErrors('phone');
        }

        $this->assertSame(0, Visitor::count());
    }

    public function test_name_is_required(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload(['full_name' => '']))
            ->assertSessionHasErrors('full_name');
    }

    public function test_unknown_consent_text_id_is_rejected(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload(['consent_text_id' => 99999]))
            ->assertSessionHasErrors('consent_text_id');
    }

    public function test_honeypot_looks_successful_but_stores_nothing(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect('/e/pinehurst/thanks');

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Message::count());
    }

    public function test_same_phone_twice_at_one_event_sends_one_text(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload());
        $this->post('/e/pinehurst/checkin', $this->payload(['phone' => '613-555-0123']))
            ->assertRedirect('/e/pinehurst/thanks');

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Message::count());
    }

    public function test_same_phone_at_a_different_event_is_a_new_visitor(): void
    {
        $other = Event::factory()->create(['agent_id' => $this->agent->id, 'slug' => 'other']);

        $this->post('/e/pinehurst/checkin', $this->payload());
        $this->post('/e/other/checkin', $this->payload());

        $this->assertSame(2, Visitor::count());
        $this->assertSame(1, Visitor::where('event_id', $other->id)->count());
    }

    public function test_checkin_after_the_event_ended_redirects_and_stores_nothing(): void
    {
        $this->event->update(['event_start' => now()->subHours(4), 'event_end' => now()->subHour()]);

        $this->post('/e/pinehurst/checkin', $this->payload())
            ->assertRedirect('https://derekbreton.ca/listing/30294107');

        $this->assertSame(0, Visitor::count());
    }

    public function test_ip_address_is_stored_in_binary_form(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload(), ['REMOTE_ADDR' => '203.0.113.9']);

        $visitor = Visitor::sole();
        $this->assertSame('203.0.113.9', inet_ntop($visitor->ip_address));
        $this->assertSame('203.0.113.9', inet_ntop(Consent::sole()->ip_address));
    }

    public function test_thanks_page_shows_the_listing_link_as_fallback(): void
    {
        $this->get('/e/pinehurst/thanks')
            ->assertOk()
            ->assertSee('href="https://derekbreton.ca/listing/30294107"', false);
    }

    public function test_checkin_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/e/pinehurst/checkin', $this->payload(['phone' => '613555'.str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT)]));
        }

        $this->post('/e/pinehurst/checkin', $this->payload(['phone' => '6135559999']))->assertStatus(429);
    }

    public function test_per_event_limit_is_keyed_on_the_event_not_the_url_slug(): void
    {
        // MySQL treats 'PINEHURST' and 'pinehurst ' as the same slug, so a limiter
        // keyed on the raw URL segment could be sidestepped. The key is the event id.
        for ($i = 0; $i < 300; $i++) {
            RateLimiter::hit('checkin-event:'.$this->event->id, 3600);
        }

        $this->post('/e/pinehurst/checkin', $this->payload())->assertStatus(429);
        $this->assertSame(0, Visitor::count());
    }

    public function test_a_concurrent_request_for_the_same_phone_does_not_create_a_second_visitor(): void
    {
        // Simulate request A holding the per-event+phone lock mid-save.
        $lock = Cache::lock("checkin:{$this->event->id}:+16135550123", 30);
        $this->assertTrue($lock->get());

        $this->post('/e/pinehurst/checkin', $this->payload())->assertRedirect('/e/pinehurst/thanks');

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Message::count());

        $lock->release();

        // Once the lock is free the same request records exactly one visitor.
        $this->post('/e/pinehurst/checkin', $this->payload());
        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Message::count());
    }

    public function test_short_link_redirects_and_counts_clicks(): void
    {
        $link = ShortLink::createFor('https://derekbreton.ca/listing/30294107', $this->event->id);

        $this->get('/s/'.$link->code)->assertRedirect('https://derekbreton.ca/listing/30294107');
        $this->get('/s/'.$link->code)->assertRedirect();

        $this->assertSame(2, $link->fresh()->clicks);
        $this->get('/s/zzzzzz')->assertNotFound();
    }

    public function test_opted_out_visitor_is_never_texted(): void
    {
        $this->post('/e/pinehurst/checkin', $this->payload());
        $visitor = Visitor::sole();
        $message = Message::sole();

        // Re-queue the message after an opt-out and confirm the sender refuses it.
        $visitor->forceFill(['sms_opt_out' => true])->save();
        $message->forceFill(['status' => 'queued', 'updated_at' => null, 'provider_message_id' => null])->save();

        app(\App\Services\MessageSender::class)->send($message->id);

        $this->assertSame('failed', $message->fresh()->status);
        $this->assertStringContainsString('opted out', $message->fresh()->error);
    }
}
