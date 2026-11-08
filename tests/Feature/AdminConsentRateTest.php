<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConsentRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_agreed_to_texts_counts_consent_given_at_check_in_even_after_a_stop(): void
    {
        $admin = Agent::factory()->admin()->create();
        $event = Event::factory()->create();
        $text = ConsentText::factory()->create();

        // Two check-ins: one still opted in, one who agreed and later replied STOP (consent revoked).
        foreach (['+19055550101' => null, '+19055550102' => now()] as $phone => $revoked) {
            $v = Visitor::create(['event_id' => $event->id, 'full_name' => 'Pat '.$phone, 'phone_e164' => $phone]);
            $v->forceFill(['sms_opt_out' => $revoked !== null])->save();
            Consent::create([
                'visitor_id' => $v->id, 'agent_id' => $event->agent_id, 'consent_text_id' => $text->id,
                'channel' => 'sms', 'granted' => true, 'revoked_at' => $revoked,
            ]);
        }
        // A third check-in who never ticked the box.
        Visitor::create(['event_id' => $event->id, 'full_name' => 'No Consent', 'phone_e164' => '+19055550103']);

        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();

        // 2 of 3 agreed at check-in = 67%, and the STOP still shows under Opted out.
        $this->assertMatchesRegularExpression('#>\s*67%\s*</p>\s*<p[^>]*>Agreed to texts#', $html);
        $this->assertMatchesRegularExpression('#>\s*1\s*</p>\s*<p[^>]*>Opted out#', $html);
    }
}
