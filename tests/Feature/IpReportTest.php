<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpReportTest extends TestCase
{
    use RefreshDatabase;

    private function visit(Event $e, ?string $ip, string $phone): void
    {
        (new Visitor)->forceFill(['event_id' => $e->id, 'full_name' => 'Secret Name', 'phone_e164' => $phone, 'ip_address' => $ip === null ? null : inet_pton($ip)])->save();
    }

    public function test_it_masks_ips_and_flags_a_shared_address(): void
    {
        $e = Event::factory()->create(['agent_id' => Agent::factory()->create()->id, 'slug' => 'ip-test']);
        foreach (['+19055550001', '+19055550002', '+19055550003'] as $p) {
            $this->visit($e, '203.0.113.45', $p);
        }

        $this->artisan('scanin:ip-report', ['slug' => 'ip-test'])
            ->expectsOutputToContain('203.0.113.x  x3')
            ->doesntExpectOutputToContain('203.0.113.45')
            ->doesntExpectOutputToContain('Secret Name')
            ->expectsOutputToContain('share one masked IP')
            ->assertSuccessful();
    }

    public function test_distinct_public_ips_look_normal_and_private_ones_are_flagged(): void
    {
        $e = Event::factory()->create(['agent_id' => Agent::factory()->create()->id, 'slug' => 'ok']);
        $this->visit($e, '203.0.113.5', '+19055550001');
        $this->visit($e, '198.51.100.7', '+19055550002');
        $this->artisan('scanin:ip-report', ['slug' => 'ok'])->expectsOutputToContain('Looks normal')->assertSuccessful();

        $p = Event::factory()->create(['agent_id' => Agent::factory()->create()->id, 'slug' => 'priv']);
        $this->visit($p, '10.0.0.2', '+19055550003');
        $this->artisan('scanin:ip-report', ['slug' => 'priv'])->expectsOutputToContain('behind a proxy')->assertSuccessful();

        $this->artisan('scanin:ip-report', ['slug' => 'nope'])->assertFailed();
    }
}
