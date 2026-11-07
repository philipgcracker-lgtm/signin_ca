<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Message;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\MessageSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundSmsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+19055550123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://scanin.ca', 'services.twilio.token' => 'test-token']);
    }

    private function visitor(?Event $event = null, string $phone = self::PHONE, string $name = 'Pat Visitor'): Visitor
    {
        $event ??= Event::factory()->create();
        $v = Visitor::create(['event_id' => $event->id, 'full_name' => $name, 'phone_e164' => $phone, 'email' => 'pat@example.com']);
        Consent::create([
            'visitor_id' => $v->id, 'agent_id' => $event->agent_id,
            'consent_text_id' => ConsentText::factory()->create(['version' => 'v-'.$v->id])->id,
            'channel' => 'sms', 'granted' => true,
        ]);

        return $v;
    }

    /** POST like Twilio does, with a valid (or overridden) signature. */
    private function inbound(string $body, array $extra = [], ?string $signature = null, string $from = self::PHONE)
    {
        $params = array_merge(['From' => $from, 'To' => '+12894028756', 'Body' => $body, 'MessageSid' => 'SM'.md5($body.microtime())], $extra);
        $sig = $signature ?? $this->sign($params);

        return $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => $sig]);
    }

    private function sign(array $params, string $token = 'test-token'): string
    {
        ksort($params);
        $data = 'https://scanin.ca/webhooks/sms/twilio';
        foreach ($params as $k => $v) {
            $data .= $k.$v;
        }

        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }

    public function test_a_bad_missing_or_unconfigured_signature_is_rejected_and_nothing_changes(): void
    {
        $v = $this->visitor();

        $this->inbound('STOP', signature: 'nope')->assertForbidden();
        $this->post('/webhooks/sms/twilio', ['From' => self::PHONE, 'Body' => 'STOP'])->assertForbidden();
        $this->inbound('STOP', signature: $this->sign(['From' => self::PHONE, 'Body' => 'STOP'], 'wrong-token'))->assertForbidden();

        config(['services.twilio.token' => null]);
        $this->inbound('STOP', signature: '')->assertForbidden();

        $this->assertFalse($v->fresh()->sms_opt_out);
        $this->assertSame(0, Message::count());
    }

    public function test_stop_opts_the_number_out_everywhere_and_revokes_consent(): void
    {
        $a = $this->visitor();
        $b = $this->visitor(Event::factory()->create()); // same phone, another event and agent
        $other = $this->visitor(null, '+19055550999', 'Someone Else');

        $res = $this->inbound('STOP')->assertOk();
        $this->assertStringContainsString('<Response></Response>', $res->getContent()); // no auto-reply: Twilio sends its own
        $this->assertStringContainsString('text/xml', $res->headers->get('Content-Type'));

        foreach ([$a, $b] as $v) {
            $this->assertTrue($v->fresh()->sms_opt_out);
            $this->assertNotNull($v->consents()->first()->revoked_at);
        }
        $this->assertFalse($other->fresh()->sms_opt_out);
        $this->assertNull($other->consents()->first()->revoked_at);

        $m = Message::sole();
        $this->assertSame('in', $m->direction);
        $this->assertSame('inbound', $m->kind);
        $this->assertSame('received', $m->status);
        $this->assertSame('STOP', $m->body);
    }

    public function test_every_stop_word_and_sloppy_formatting_works(): void
    {
        foreach (['stop', '  Stop. ', 'STOPALL', 'unsubscribe', 'Cancel', 'END', 'quit', 'Opt out', 'REVOKE'] as $word) {
            $v = $this->visitor(null, self::PHONE, 'P '.$word);
            $this->inbound($word)->assertOk();
            $this->assertTrue($v->fresh()->sms_opt_out, "'{$word}' should opt out");
        }
    }

    public function test_twilios_own_opt_out_flag_counts_too(): void
    {
        $v = $this->visitor();

        $this->inbound('please no more', ['OptOutType' => 'STOP'])->assertOk();

        $this->assertTrue($v->fresh()->sms_opt_out);
    }

    public function test_other_messages_do_nothing_and_start_never_re_enables(): void
    {
        $v = $this->visitor();

        $this->inbound('what time does it start?')->assertOk();
        $this->inbound('I stopped by yesterday')->assertOk(); // contains the word, is not the word
        $this->assertFalse($v->fresh()->sms_opt_out);
        $this->assertNull($v->consents()->first()->revoked_at);
        $this->assertSame(2, Message::count()); // recorded as ordinary replies

        $this->inbound('STOP');
        $this->inbound('START')->assertOk();
        $this->inbound('UNSTOP')->assertOk();
        $this->assertTrue($v->fresh()->sms_opt_out);
    }

    public function test_an_unknown_number_is_accepted_and_nothing_is_stored(): void
    {
        $this->visitor();

        $this->inbound('STOP', from: '+16135550000')->assertOk();

        $this->assertSame(0, Message::count());
    }

    public function test_a_webhook_retry_does_not_duplicate_the_inbound_message(): void
    {
        $this->visitor();
        $params = ['From' => self::PHONE, 'To' => '+12894028756', 'Body' => 'STOP', 'MessageSid' => 'SMretry1'];

        $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => $this->sign($params)])->assertOk();
        $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => $this->sign($params)])->assertOk();

        $this->assertSame(1, Message::count());
    }

    public function test_delete_erases_personal_data_keeps_the_revoked_consent_and_replies(): void
    {
        $event = Event::factory()->create();
        $v = $this->visitor($event);
        $v->forceFill(['feedback_score' => 4, 'feedback_notes' => 'loved the kitchen', 'buying_timeline' => 'immediate'])->save();
        ShortLink::createFor($event->listing_url, $event->id, $v->id);
        Message::create([
            'visitor_id' => $v->id, 'event_id' => $event->id, 'direction' => 'out', 'channel' => 'sms', 'kind' => 'checkin',
            'to_addr' => self::PHONE, 'from_addr' => '+12894028756', 'body' => 'Thanks for visiting', 'provider' => 'twilio',
            'status' => 'failed', 'error' => "The 'To' number +19055550123 is not valid",
        ]);

        $res = $this->inbound('Delete')->assertOk();
        $this->assertStringContainsString('<Message>Your information has been deleted', $res->getContent());

        $v->refresh();
        $this->assertNotNull($v->anonymized_at);
        $this->assertSame('Deleted', $v->full_name);
        $this->assertSame('deleted-'.$v->id, $v->phone_e164);
        $this->assertNull($v->email);
        $this->assertNull($v->buying_timeline);
        $this->assertNull($v->feedback_notes);
        $this->assertSame(4, $v->feedback_score);
        $this->assertTrue($v->sms_opt_out);

        $this->assertSame(0, ShortLink::count());
        $this->assertNotNull($v->consents()->sole()->revoked_at); // proof of consent is kept, marked revoked

        foreach (Message::all() as $m) {
            $this->assertStringNotContainsString('9055550123', json_encode($m->getAttributes()));
            $this->assertSame('[deleted]', $m->body);
            $this->assertNull($m->error);
        }
        $this->assertSame(2, Message::count()); // the check-in text and the DELETE itself
    }

    public function test_a_deleted_visitor_shows_as_removed_on_the_agent_dashboard_and_is_not_exported(): void
    {
        $event = Event::factory()->create();
        $v = $this->visitor($event, self::PHONE, 'Pat Visitor');

        $this->inbound('DELETE')->assertOk();

        $agent = $event->agent;
        $this->actingAs($agent)->get("/events/{$event->id}")->assertOk()
            ->assertSee('Personal details removed')->assertDontSee('Pat Visitor')->assertDontSee('pat@example.com');
        $csv = $this->actingAs($agent)->get("/events/{$event->id}/visitors.csv")->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Pat Visitor', $csv);
        $this->assertStringNotContainsString('9055550123', $csv);
    }

    public function test_opt_out_follows_the_phone_to_a_later_check_in(): void
    {
        $this->visitor();
        $this->inbound('STOP')->assertOk();

        // The same person checks in at another open house: a fresh visitor row, opt-out flag unset.
        $event2 = Event::factory()->create();
        $again = Visitor::create(['event_id' => $event2->id, 'full_name' => 'Pat Again', 'phone_e164' => self::PHONE]);
        $text = Message::create([
            'visitor_id' => $again->id, 'event_id' => $event2->id, 'direction' => 'out', 'channel' => 'sms', 'kind' => 'checkin',
            'to_addr' => self::PHONE, 'from_addr' => '+12894028756', 'body' => 'hi', 'provider' => 'twilio', 'status' => 'queued',
        ]);

        app(MessageSender::class)->send($text->id);

        $text->refresh();
        $this->assertSame('failed', $text->status);
        $this->assertSame('Suppressed: visitor opted out.', $text->error);
    }
}
