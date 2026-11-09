<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionSweepTest extends TestCase
{
    use RefreshDatabase;

    private function visitorAt(Event $event, string $name = 'Pat Visitor', string $phone = '+19055550123'): Visitor
    {
        $v = new Visitor;
        $v->forceFill(['event_id' => $event->id, 'full_name' => $name, 'phone_e164' => $phone, 'email' => 'pat@example.com', 'feedback_score' => 4])->save();

        return $v;
    }

    private function endedMonthsAgo(Agent $agent, int $months, int $extraDays = 1): Event
    {
        return Event::factory()->create([
            'agent_id' => $agent->id,
            'event_start' => now()->subMonths($months)->subDays($extraDays)->subHours(2),
            'event_end' => now()->subMonths($months)->subDays($extraDays),
            'status' => 'ended',
        ]);
    }

    public function test_visitors_past_24_months_are_anonymized_and_recent_ones_are_not(): void
    {
        $agent = Agent::factory()->create();
        $old = $this->visitorAt($this->endedMonthsAgo($agent, 24));
        $recent = $this->visitorAt($this->endedMonthsAgo($agent, 23), 'Recent', '+19055550124');

        $this->artisan('visitors:retention-sweep')->assertSuccessful();

        $old->refresh();
        $this->assertNotNull($old->anonymized_at);
        $this->assertSame('Deleted', $old->full_name);
        $this->assertNull($old->email);
        $this->assertSame(4, $old->feedback_score);

        $recent->refresh();
        $this->assertNull($recent->anonymized_at);
        $this->assertSame('Recent', $recent->full_name);
    }

    public function test_the_agents_own_retention_months_is_used_but_never_under_12(): void
    {
        $short = Agent::factory()->create(['retention_months' => 12]);
        $tiny = Agent::factory()->create(['retention_months' => 3]);
        $long = Agent::factory()->create(['retention_months' => 36]);

        $a = $this->visitorAt($this->endedMonthsAgo($short, 13), 'A', '+19055550001');
        $b = $this->visitorAt($this->endedMonthsAgo($tiny, 6), 'B', '+19055550002');
        $c = $this->visitorAt($this->endedMonthsAgo($tiny, 13), 'C', '+19055550003');
        $d = $this->visitorAt($this->endedMonthsAgo($long, 30), 'D', '+19055550004');

        $this->artisan('visitors:retention-sweep')->assertSuccessful();

        $this->assertNotNull($a->fresh()->anonymized_at);
        $this->assertNull($b->fresh()->anonymized_at, 'a 3-month setting is raised to the 12-month floor');
        $this->assertNotNull($c->fresh()->anonymized_at);
        $this->assertNull($d->fresh()->anonymized_at);
    }

    public function test_dry_run_changes_nothing_and_a_second_run_is_a_no_op(): void
    {
        $agent = Agent::factory()->create();
        $v = $this->visitorAt($this->endedMonthsAgo($agent, 30));

        $this->artisan('visitors:retention-sweep', ['--dry-run' => true])
            ->expectsOutputToContain('would anonymize 1 visitor(s) across 1 event(s)')->assertSuccessful();
        $this->assertNull($v->fresh()->anonymized_at);

        $this->artisan('visitors:retention-sweep')->expectsOutputToContain('anonymized 1 visitor(s)')->assertSuccessful();
        $at = $v->fresh()->anonymized_at;
        $this->assertNotNull($at);

        $this->artisan('visitors:retention-sweep')->expectsOutputToContain('anonymized 0 visitor(s)')->assertSuccessful();
        $this->assertEquals($at, $v->fresh()->anonymized_at);
    }

    public function test_consent_records_survive_and_are_marked_revoked(): void
    {
        $agent = Agent::factory()->create();
        $text = ConsentText::factory()->create();
        $v = $this->visitorAt($this->endedMonthsAgo($agent, 30));
        $c = new Consent;
        $c->forceFill(['visitor_id' => $v->id, 'agent_id' => $agent->id, 'consent_text_id' => $text->id, 'channel' => 'sms', 'granted' => true])->save();

        $this->artisan('visitors:retention-sweep')->assertSuccessful();

        $this->assertNotNull(Consent::find($c->id)->revoked_at);
    }

    public function test_cancelled_deleted_events_and_deleted_agents_are_still_swept(): void
    {
        $agent = Agent::factory()->create();
        $cancelled = $this->endedMonthsAgo($agent, 30);
        $cancelled->update(['status' => 'cancelled']);
        $a = $this->visitorAt($cancelled, 'A', '+19055550001');

        $trashed = $this->endedMonthsAgo($agent, 30);
        $b = $this->visitorAt($trashed, 'B', '+19055550002');
        $trashed->delete();

        $gone = Agent::factory()->create();
        $c = $this->visitorAt($this->endedMonthsAgo($gone, 30), 'C', '+19055550003');
        $gone->delete();

        $this->artisan('visitors:retention-sweep')->assertSuccessful();

        foreach ([$a, $b, $c] as $v) {
            $this->assertNotNull($v->fresh()->anonymized_at);
        }
    }

    public function test_the_sweep_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('visitors:retention-sweep')->assertSuccessful();
    }
}
