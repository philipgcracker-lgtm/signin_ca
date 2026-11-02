<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\Visitor;
use App\Services\MessageSender;
use App\Sms\SmsException;
use App\Sms\SmsProvider;
use App\Sms\SmsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingJobsTest extends TestCase
{
    use RefreshDatabase;

    private function queuedMessage(array $overrides = []): Message
    {
        $event = Event::factory()->create();
        $visitor = Visitor::create([
            'event_id' => $event->id,
            'full_name' => 'Pat',
            'phone_e164' => '+16135550123',
        ]);

        $message = new Message(array_merge([
            'visitor_id' => $visitor->id,
            'event_id' => $event->id,
            'direction' => 'out',
            'channel' => 'sms',
            'kind' => 'checkin',
            'to_addr' => '+16135550123',
            'from_addr' => 'log',
            'body' => 'hello',
            'provider' => 'log',
            'status' => 'queued',
        ], collect($overrides)->except('created_at')->all()));

        if (isset($overrides['created_at'])) {
            $message->created_at = $overrides['created_at'];
        }

        $message->save();

        return $message;
    }

    public function test_sender_claims_a_message_once(): void
    {
        $counter = new class implements SmsProvider
        {
            public int $sent = 0;

            public function name(): string { return 'fake'; }

            public function from(): string { return 'fake'; }

            public function send(string $toE164, string $body): SmsResult
            {
                $this->sent++;

                return new SmsResult('sent', 'id-1');
            }
        };
        $sender = new MessageSender($counter);
        $message = $this->queuedMessage();

        $sender->send($message->id);
        $sender->send($message->id);

        $this->assertSame(1, $counter->sent);
        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame('id-1', $message->fresh()->provider_message_id);
    }

    public function test_provider_failure_is_recorded_not_thrown(): void
    {
        $failing = new class implements SmsProvider
        {
            public function name(): string { return 'fake'; }

            public function from(): string { return 'fake'; }

            public function send(string $toE164, string $body): SmsResult
            {
                throw new SmsException('Twilio HTTP 400 (code 21211): Invalid To number');
            }
        };
        $message = $this->queuedMessage();

        (new MessageSender($failing))->send($message->id);

        $this->assertSame('failed', $message->fresh()->status);
        $this->assertStringContainsString('21211', $message->fresh()->error);
    }

    public function test_sweep_sends_old_queued_messages_only(): void
    {
        $old = $this->queuedMessage(['created_at' => now()->subMinutes(2)]);
        $fresh = $this->queuedMessage(['created_at' => now()->subSeconds(5)]);
        $alreadySent = $this->queuedMessage(['created_at' => now()->subMinutes(5), 'status' => 'sent']);

        $this->artisan('messages:send-queued')->assertSuccessful();

        $this->assertSame('sent', $old->fresh()->status);
        $this->assertSame('queued', $fresh->fresh()->status);
        $this->assertSame('sent', $alreadySent->fresh()->status);
    }

    public function test_seed_pilot_is_idempotent_and_requires_an_existing_agent(): void
    {
        $this->artisan('scanin:seed-pilot')->assertFailed();
        $this->artisan('scanin:seed-pilot', ['--agent' => 'nobody@example.com'])->assertFailed();

        $agent = Agent::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('scanin:seed-pilot', ['--agent' => 'owner@example.com'])->assertSuccessful();
        $this->artisan('scanin:seed-pilot', ['--agent' => 'owner@example.com'])->assertSuccessful();

        $this->assertSame(1, Event::where('slug', 'pinehurst-nov8')->count());
        $this->assertSame(1, ConsentText::where('version', 'v0-draft')->count());

        $event = Event::where('slug', 'pinehurst-nov8')->sole();
        $this->assertSame($agent->id, $event->agent_id);
        $this->assertSame('74 Pinehurst Drive', $event->address_street);
        $this->assertSame('https://derekbreton.ca/listing/30294107', $event->listing_url);

        // 2026-11-08 14:00 to 16:00 Toronto time, whatever the app timezone is.
        $this->assertSame('2026-11-08 14:00', $event->event_start->copy()->setTimezone('America/Toronto')->format('Y-m-d H:i'));
        $this->assertSame('2026-11-08 16:00', $event->event_end->copy()->setTimezone('America/Toronto')->format('Y-m-d H:i'));

        // Blank profile fields were filled; nothing the owner typed is overwritten.
        $this->assertNotEmpty($agent->fresh()->brokerage_name);
    }

    public function test_seed_pilot_never_overwrites_existing_profile_fields(): void
    {
        $agent = Agent::factory()->create(['email' => 'owner@example.com', 'brokerage_name' => 'My Own Brokerage']);

        $this->artisan('scanin:seed-pilot', ['--agent' => 'owner@example.com'])->assertSuccessful();

        $this->assertSame('My Own Brokerage', $agent->fresh()->brokerage_name);
    }
}
