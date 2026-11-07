<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\MessageSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAgentsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Agent
    {
        return Agent::factory()->admin()->create();
    }

    public function test_guests_go_to_login_and_non_admins_get_a_404(): void
    {
        $this->get('/admin/agents')->assertRedirect('/login');

        $agent = Agent::factory()->create();
        $this->actingAs($agent)->get('/admin/agents')->assertNotFound();
        $this->actingAs($agent)->post("/admin/agents/{$agent->id}/approve")->assertNotFound();
    }

    public function test_a_suspended_admin_loses_access(): void
    {
        $admin = Agent::factory()->admin()->suspended()->create();

        $this->actingAs($admin)->get('/admin/agents')->assertNotFound();
    }

    public function test_is_admin_is_not_mass_assignable(): void
    {
        $agent = Agent::create([
            'full_name' => 'Sneaky', 'email' => 'sneaky@example.com', 'password_hash' => 'x',
            'is_admin' => true, 'suspended_at' => null, 'comped_at' => now(),
        ]);

        $this->assertFalse((bool) $agent->fresh()->is_admin);
        $this->assertNull($agent->fresh()->comped_at);
    }

    public function test_the_admin_sees_the_agent_list_with_counts_and_no_visitor_details(): void
    {
        $admin = $this->admin();
        $agent = Agent::factory()->unapproved()->create(['full_name' => 'Pending Pat', 'brokerage_name' => 'Acme Realty']);
        $busy = Agent::factory()->create(['full_name' => 'Busy Bea']);
        $event = Event::factory()->create(['agent_id' => $busy->id]);
        $v = Visitor::create(['event_id' => $event->id, 'full_name' => 'Secret Visitor', 'phone_e164' => '+19055550123']);
        foreach (['sent', 'failed'] as $status) {
            Message::create([
                'visitor_id' => $v->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms',
                'kind' => 'checkin', 'to_addr' => '+19055550123', 'from_addr' => '+12894028756', 'body' => 'hi',
                'provider' => 'twilio', 'status' => $status,
            ]);
        }

        $res = $this->actingAs($admin)->get('/admin/agents')->assertOk();
        $res->assertSee('Pending Pat')->assertSee('Acme Realty')->assertSee('Busy Bea')->assertSee('1 waiting for approval');
        $res->assertSee('(1 failed)');
        $res->assertDontSee('Secret Visitor')->assertDontSee('9055550123');

        // Pending agents are listed first.
        $this->assertSame($agent->id, $res->viewData('agents')->first()->id);

        $this->actingAs($admin)->get("/admin/agents/{$busy->id}")->assertOk()
            ->assertSee($event->listing_url)
            ->assertDontSee('Secret Visitor')->assertDontSee('9055550123');
    }

    public function test_approve_and_revoke(): void
    {
        $admin = $this->admin();
        $agent = Agent::factory()->unapproved()->create();

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/approve")->assertRedirect();
        $this->assertTrue($agent->fresh()->isApproved());

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/revoke")->assertRedirect();
        $this->assertFalse($agent->fresh()->isApproved());
    }

    public function test_set_plan_marks_pro_as_comped(): void
    {
        $admin = $this->admin();
        $agent = Agent::factory()->create();

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/tier", ['tier' => 'pro'])->assertRedirect();
        $agent->refresh();
        $this->assertSame('pro', $agent->subscription_tier);
        $this->assertNotNull($agent->comped_at);

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/tier", ['tier' => 'free'])->assertRedirect();
        $agent->refresh();
        $this->assertSame('free', $agent->subscription_tier);
        $this->assertNull($agent->comped_at);

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/tier", ['tier' => 'platinum'])->assertSessionHasErrors('tier');
    }

    public function test_the_admin_cannot_lock_themselves_out(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/agents/{$admin->id}/suspend")->assertSessionHas('error');
        $this->actingAs($admin)->post("/admin/agents/{$admin->id}/revoke")->assertSessionHas('error');

        $admin->refresh();
        $this->assertFalse($admin->isSuspended());
        $this->assertTrue($admin->isApproved());
    }

    public function test_suspending_turns_everything_off_and_restoring_turns_it_back_on(): void
    {
        config(['app.url' => 'https://scanin.ca']);
        $consent = ConsentText::factory()->create(['version' => 'v-test']);
        $admin = $this->admin();
        $agent = Agent::factory()->create();
        $event = Event::factory()->create([
            'agent_id' => $agent->id, 'slug' => 'pinehurst',
            'event_start' => now()->subHour(), 'event_end' => now()->addHour(), 'status' => 'live',
        ]);
        $visitor = Visitor::create(['event_id' => $event->id, 'full_name' => 'V', 'phone_e164' => '+19055550111']);
        $link = ShortLink::createFor($event->listing_url, $event->id, $visitor->id);
        $message = fn () => Message::create([
            'visitor_id' => $visitor->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms',
            'kind' => 'checkin', 'to_addr' => '+19055550111', 'from_addr' => '+12894028756', 'body' => 'hi',
            'provider' => 'twilio', 'status' => 'queued',
        ]);

        $this->get('/e/pinehurst')->assertOk();
        $this->get('/s/'.$link->code)->assertRedirect($event->listing_url);

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/suspend")->assertRedirect();
        auth()->logout();

        $this->get('/e/pinehurst')->assertNotFound();
        $this->post('/e/pinehurst/checkin', [
            'full_name' => 'Pat Visitor', 'phone' => '(613) 555-0123', 'consent' => '1', 'consent_text_id' => $consent->id,
        ])->assertNotFound();
        $this->assertSame(1, Visitor::count()); // only the seeded one: nothing was saved
        $this->get('/s/'.$link->code)->assertNotFound();

        $suppressed = $message();
        app(MessageSender::class)->send($suppressed->id);
        $suppressed->refresh();
        $this->assertSame('failed', $suppressed->status);
        $this->assertSame('Suppressed: account suspended.', $suppressed->error);

        $this->actingAs($agent->fresh())->get('/events/create')->assertRedirect('/dashboard');
        $this->actingAs($agent->fresh())->post('/events', [
            'address_street' => '12 Maple Street', 'city' => 'Welland',
            'event_date' => now('America/Toronto')->addDays(3)->format('Y-m-d'),
            'start_time' => '14:00', 'end_time' => '16:00', 'listing_url' => 'https://derekbreton.ca/listing/1',
        ])->assertRedirect('/dashboard');
        $this->assertSame(1, Event::count()); // still only the seeded event
        $this->actingAs($agent->fresh())->get('/dashboard')->assertOk()->assertSee('Your account is suspended');

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/unsuspend")->assertRedirect();
        auth()->logout();

        $this->get('/e/pinehurst')->assertOk();
        $this->get('/s/'.$link->code)->assertRedirect($event->listing_url);
    }

    public function test_make_admin_command(): void
    {
        $agent = Agent::factory()->unapproved()->create(['email' => 'boss@example.com']);

        $this->artisan('scanin:make-admin', ['email' => 'BOSS@example.com'])->assertSuccessful();
        $agent->refresh();
        $this->assertTrue((bool) $agent->is_admin);
        $this->assertTrue($agent->isApproved());

        $this->artisan('scanin:make-admin', ['email' => 'boss@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse((bool) $agent->fresh()->is_admin);

        $this->artisan('scanin:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_the_nav_shows_admin_only_to_admins(): void
    {
        $this->actingAs(Agent::factory()->create())->get('/dashboard')->assertDontSee('/admin');
        $this->actingAs($this->admin())->get('/dashboard')->assertSee('/admin');
    }

    public function test_the_overview_is_admin_only(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(Agent::factory()->create())->get('/admin')->assertNotFound();
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Overview');
    }

    public function test_the_overview_shows_platform_totals_and_no_personal_data(): void
    {
        $admin = $this->admin();
        Agent::factory()->unapproved()->create();
        Agent::factory()->suspended()->create();
        $agent = Agent::factory()->create(['full_name' => 'Busy Bea']);
        $agent->forceFill(['subscription_tier' => 'pro', 'comped_at' => now()])->save();

        $live = Event::factory()->create(['agent_id' => $agent->id, 'event_start' => now()->subHour(), 'event_end' => now()->addHour(), 'status' => 'live']);
        Event::factory()->create(['agent_id' => $agent->id, 'event_start' => now()->addDays(2), 'event_end' => now()->addDays(2)->addHours(2), 'status' => 'upcoming']);

        $a = Visitor::create(['event_id' => $live->id, 'full_name' => 'Secret Visitor', 'phone_e164' => '+19055550123']);
        $b = Visitor::create(['event_id' => $live->id, 'full_name' => 'Other Person', 'phone_e164' => '+19055550124']);
        foreach ([['sent', 2], ['failed', null]] as [$status, $cost]) {
            Message::create([
                'visitor_id' => $a->id, 'event_id' => $live->id, 'direction' => 'out', 'channel' => 'sms',
                'kind' => 'checkin', 'to_addr' => '+19055550123', 'from_addr' => '+12894028756', 'body' => 'secret body',
                'provider' => 'twilio', 'status' => $status, 'cost_cents' => $cost,
            ]);
        }
        $l1 = ShortLink::createFor($live->listing_url, $live->id, $a->id);
        $l1->forceFill(['clicks' => 3])->save();
        ShortLink::createFor($live->listing_url, $live->id, $b->id);

        $res = $this->actingAs($admin)->get('/admin')->assertOk();

        $res->assertViewHas('agents', fn ($s) => $s['total'] === 4 && $s['pending'] === 1 && $s['suspended'] === 1 && $s['pro'] === 1 && $s['comped'] === 1);
        $res->assertViewHas('events', fn ($s) => $s['total'] === 2 && $s['live'] === 1 && $s['upcoming'] === 1 && $s['ended'] === 0);
        $res->assertViewHas('visitors', fn ($s) => $s['total'] === 2);
        $res->assertViewHas('texts', fn ($s) => $s['month'] === 2 && $s['failed_month'] === 1 && $s['failure_rate'] === 50 && $s['cost_month_cents'] === 2);
        $res->assertViewHas('engagement', fn ($s) => $s['links'] === 2 && $s['clicked'] === 1 && $s['rate'] === 50 && $s['clicks'] === 3);
        $res->assertViewHas('checkinSeries', fn ($s) => count($s) === 30 && array_sum($s) === 2);
        $res->assertSee('Busy Bea')->assertSee('Top agents');
        $res->assertDontSee('Secret Visitor')->assertDontSee('Other Person')->assertDontSee('9055550123')->assertDontSee('secret body');
    }

    public function test_the_overview_renders_with_an_empty_database(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('No check-ins yet.');
    }
}
