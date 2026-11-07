<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTestVisitorsTest extends TestCase
{
    use RefreshDatabase;

    private function seedVisitor(Event $event, string $phone): Visitor
    {
        $v = Visitor::create(['event_id' => $event->id, 'full_name' => 'Test '.$phone, 'phone_e164' => $phone]);
        Consent::create([
            'visitor_id' => $v->id, 'agent_id' => $event->agent_id,
            'consent_text_id' => ConsentText::factory()->create(['version' => 'v-'.$phone])->id,
            'channel' => 'sms', 'granted' => true,
        ]);
        Message::create([
            'visitor_id' => $v->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms',
            'kind' => 'checkin', 'to_addr' => $phone, 'from_addr' => '+12894028756', 'body' => 'hi',
            'provider' => 'twilio', 'status' => 'failed',
        ]);

        return $v;
    }

    public function test_deletes_visitors_consents_and_messages_when_the_count_matches(): void
    {
        $event = Event::factory()->create(['slug' => 'pinehurst']);
        $this->seedVisitor($event, '+12898213576');
        $this->seedVisitor($event, '+19055550100');

        $this->artisan('scanin:delete-test-visitors', ['slug' => 'pinehurst', '--expect' => 2])->assertSuccessful();

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Consent::count());
        $this->assertSame(0, Message::count());
    }

    public function test_deletes_nothing_when_the_count_differs(): void
    {
        $event = Event::factory()->create(['slug' => 'pinehurst']);
        $this->seedVisitor($event, '+12898213576');
        $this->seedVisitor($event, '+19055550100');
        $this->seedVisitor($event, '+19055550101');

        $this->artisan('scanin:delete-test-visitors', ['slug' => 'pinehurst', '--expect' => 2])->assertFailed();

        $this->assertSame(3, Visitor::count());
        $this->assertSame(3, Message::count());
    }

    public function test_only_touches_the_named_event_and_needs_expect(): void
    {
        $a = Event::factory()->create(['slug' => 'a']);
        $b = Event::factory()->create(['slug' => 'b']);
        $this->seedVisitor($a, '+12898213576');
        $this->seedVisitor($b, '+19055550100');

        $this->artisan('scanin:delete-test-visitors', ['slug' => 'a'])->assertFailed();
        $this->artisan('scanin:delete-test-visitors', ['slug' => 'nope', '--expect' => 1])->assertFailed();
        $this->artisan('scanin:delete-test-visitors', ['slug' => 'a', '--expect' => 1])->assertSuccessful();

        $this->assertSame(1, Visitor::count());
        $this->assertSame($b->id, Visitor::first()->event_id);
    }
}
