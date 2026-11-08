<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Event;
use App\Models\Message;
use App\Models\Visitor;
use App\Services\SchedulerHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_heartbeat_is_on_the_schedule_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'scheduler-heartbeat');

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_running_the_heartbeat_marks_the_scheduler_as_running(): void
    {
        $this->assertFalse(SchedulerHeartbeat::isRunning());
        $this->assertNull(SchedulerHeartbeat::lastBeat());

        SchedulerHeartbeat::beat();

        $this->assertTrue(SchedulerHeartbeat::isRunning());
    }

    public function test_it_counts_as_down_after_three_minutes(): void
    {
        SchedulerHeartbeat::beat();
        $this->travel(4)->minutes();

        $this->assertFalse(SchedulerHeartbeat::isRunning());
    }

    public function test_the_admin_overview_shows_scheduler_state(): void
    {
        $admin = Agent::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('No signal yet');

        SchedulerHeartbeat::beat();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Running')->assertDontSee('No signal yet');

        $this->travel(10)->minutes();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Not running');
    }

    public function test_old_queued_texts_are_counted_as_stuck_and_fresh_ones_are_not(): void
    {
        $admin = Agent::factory()->admin()->create();
        $event = Event::factory()->create();
        $v = Visitor::create(['event_id' => $event->id, 'full_name' => 'Pat', 'phone_e164' => '+19055550123']);
        $make = fn () => Message::create([
            'visitor_id' => $v->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms', 'kind' => 'checkin',
            'to_addr' => '+19055550123', 'from_addr' => '+12894028756', 'body' => 'hi', 'provider' => 'twilio', 'status' => 'queued',
        ]);

        $make();
        $this->travel(6)->minutes();
        $make(); // created just now: not stuck yet

        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<p class="text-2xl[^>]*>\s*1\s*</p>\s*<p[^>]*>Texts stuck in queue#', $html);
    }
}
