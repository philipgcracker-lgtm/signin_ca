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
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Fixes from the 2026-11-08 QA review: deleted-agent QR, friendly refusal pages,
 * consent-wording id from the browser, paper entry for opted-out numbers, and
 * plain-language / French opt-outs.
 */
class QaHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    private ConsentText $online;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://scanin.ca', 'services.twilio.token' => 'test-token']);

        $this->agent = Agent::factory()->create(['full_name' => 'Derek Breton']);
        $this->online = ConsentText::factory()->create(['version' => 'online-test']);
        $this->event = Event::factory()->create([
            'agent_id' => $this->agent->id,
            'slug' => 'qa-house',
            'listing_url' => 'https://derekbreton.ca/listing/1',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Pat Visitor',
            'phone' => '(613) 555-0123',
            'consent' => '1',
            'consent_text_id' => $this->online->id,
        ], $overrides);
    }

    public function test_a_deleted_agents_qr_is_a_404_not_a_500(): void
    {
        $link = ShortLink::createFor($this->event->listing_url, $this->event->id, null);
        $this->agent->delete();

        $this->get('/e/qa-house')->assertNotFound();
        $this->post('/e/qa-house/checkin', $this->payload())->assertNotFound();
        $this->get('/e/qa-house/thanks')->assertNotFound();
        $this->get('/s/'.$link->code)->assertNotFound();
        $this->assertSame(0, Visitor::count());
    }

    public function test_a_suspended_agents_thanks_page_is_hidden(): void
    {
        $this->agent->forceFill(['suspended_at' => now()])->save();

        $this->get('/e/qa-house/thanks')->assertNotFound();
    }

    public function test_the_404_page_is_readable_and_not_the_framework_default(): void
    {
        $this->get('/e/does-not-exist')
            ->assertNotFound()
            ->assertSee("This page isn't available", false);
    }

    public function test_a_refused_checkin_keeps_the_listing_link(): void
    {
        for ($i = 0; $i < 300; $i++) {
            RateLimiter::hit('checkin-event:'.$this->event->id, 3600);
        }

        $this->post('/e/qa-house/checkin', $this->payload())
            ->assertStatus(429)
            ->assertSee("That didn't go through", false)
            ->assertSee('https://derekbreton.ca/listing/1', false);

        $this->assertSame(0, Visitor::count());
    }

    public function test_a_cancelled_event_shows_no_retry_page(): void
    {
        $this->event->forceFill(['status' => 'cancelled'])->save();
        for ($i = 0; $i < 300; $i++) {
            RateLimiter::hit('checkin-event:'.$this->event->id, 3600);
        }

        $this->post('/e/qa-house/checkin', $this->payload())->assertNotFound();
    }

    public function test_the_per_ip_limit_now_allows_a_busy_open_house(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/e/qa-house/checkin', $this->payload(['phone' => '613555'.str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT)]))
                ->assertRedirect();
        }

        $this->post('/e/qa-house/checkin', $this->payload(['phone' => '6135559999']))->assertStatus(429);
    }

    public function test_a_paper_or_future_wording_id_is_rejected_on_the_online_form(): void
    {
        $paper = ConsentText::query()->where('version', 'like', 'paper-%')->first()
            ?? ConsentText::factory()->create(['version' => 'paper-x']);
        $future = ConsentText::factory()->create(['version' => 'online-future', 'effective_from' => now()->addDay()]);

        foreach ([$paper->id, $future->id] as $id) {
            $this->post('/e/qa-house/checkin', $this->payload(['consent_text_id' => $id]))
                ->assertSessionHasErrors('consent_text_id');
        }

        $this->assertSame(0, Visitor::count());

        $this->post('/e/qa-house/checkin', $this->payload())->assertRedirect(route('checkin.thanks', $this->event));
        $this->assertSame(1, Visitor::count());
    }

    public function test_an_older_online_wording_already_in_force_is_still_accepted(): void
    {
        $older = ConsentText::factory()->create(['version' => 'online-older', 'effective_from' => now()->subDays(10)]);

        $this->post('/e/qa-house/checkin', $this->payload(['consent_text_id' => $older->id]))->assertRedirect();
        $this->assertSame(1, Visitor::count());
    }

    public function test_paper_entry_for_an_opted_out_number_saves_the_lead_but_queues_no_text(): void
    {
        $other = Event::factory()->create();
        $old = Visitor::create(['event_id' => $other->id, 'full_name' => 'Old Row', 'phone_e164' => '+19055550111']);
        $old->forceFill(['sms_opt_out' => true])->save();

        $this->actingAs($this->agent)
            ->post("/events/{$this->event->id}/visitors", ['full_name' => 'Priya Sharma', 'phone' => '(905) 555-0111', 'sms_ok' => '1'])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'no text could be sent') && ! str_contains($s, 'opted'));

        $this->assertSame(1, Visitor::where('event_id', $this->event->id)->count());
        $this->assertSame(0, Consent::where('visitor_id', Visitor::where('event_id', $this->event->id)->value('id'))->count());
        $this->assertSame(0, Message::count());
    }

    // ---- plain-language and French opt-outs -------------------------------------------------

    private function inbound(string $body, string $from = '+19055550123')
    {
        $params = ['From' => $from, 'To' => '+12894028756', 'Body' => $body, 'MessageSid' => 'SM'.md5($body.microtime())];
        ksort($params);
        $data = 'https://scanin.ca/webhooks/sms/twilio';
        foreach ($params as $k => $v) {
            $data .= $k.$v;
        }
        $sig = base64_encode(hash_hmac('sha1', $data, 'test-token', true));

        return $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => $sig]);
    }

    /** @return array<string, array{0: string}> */
    public static function optOutMessages(): array
    {
        return [
            'please stop' => ['Please stop texting me'],
            'remove me' => ["Hi, remove me from your list please."],
            'no more texts' => ['No more texts!'],
            'do not text' => ["Do not text me again"],
            'dont text' => ["Don't text me"],
            'unsubscribe me' => ['Unsubscribe me'],
            'french arret' => ['Arrêt'],
            'french arrete' => ['Arrête de m\'écrire'],
            'french annuler' => ['ANNULER'],
            'french desabonner' => ['Désabonner'],
        ];
    }

    /**
     * @dataProvider optOutMessages
     */
    public function test_clear_withdrawals_in_a_sentence_or_in_french_opt_the_number_out(string $body): void
    {
        $v = Visitor::create(['event_id' => $this->event->id, 'full_name' => 'Pat', 'phone_e164' => '+19055550123']);

        $this->inbound($body)->assertOk();

        $this->assertTrue((bool) $v->fresh()->sms_opt_out, "'{$body}' should opt the number out");
    }

    public function test_ordinary_replies_do_not_opt_the_number_out(): void
    {
        $v = Visitor::create(['event_id' => $this->event->id, 'full_name' => 'Pat', 'phone_e164' => '+19055550123']);

        foreach (['Thanks, can I see the house again Saturday?', 'What is the lot size?', 'Is the street busy?'] as $body) {
            $this->inbound($body)->assertOk();
        }

        $this->assertFalse((bool) $v->fresh()->sms_opt_out);
    }
}
