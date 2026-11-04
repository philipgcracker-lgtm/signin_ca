<?php

namespace Tests\Feature;

use App\Http\Controllers\VisitorExportController;
use App\Models\Agent;
use App\Models\Consent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorExportTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    private ConsentText $text;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = Agent::factory()->create();
        $this->event = Event::factory()->create(['agent_id' => $this->agent->id, 'slug' => 'pinehurst']);
        $this->text = ConsentText::factory()->create(['version' => 'v-test']);
    }

    private function visitor(array $attrs = []): Visitor
    {
        $v = new Visitor;
        $v->forceFill(array_merge([
            'event_id' => $this->event->id,
            'full_name' => 'Priya Sharma',
            'phone_e164' => '+19055550111',
        ], $attrs))->save();

        return $v;
    }

    private function consent(Visitor $v, string $purpose = 'checkin', array $attrs = []): Consent
    {
        $c = new Consent;
        $c->forceFill(array_merge([
            'visitor_id' => $v->id, 'agent_id' => $this->agent->id, 'consent_text_id' => $this->text->id,
            'channel' => $purpose === 'marketing' ? 'email' : 'sms', 'purpose' => $purpose, 'granted' => true,
        ], $attrs))->save();

        return $c;
    }

    /** @return array<int, array<int, string>> rows without the header */
    private function rows(string $csv): array
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");
        $lines = array_map('str_getcsv', array_filter(explode("\n", $csv), fn ($l) => trim($l) !== ''));
        array_shift($lines);

        return $lines;
    }

    public function test_guests_are_redirected(): void
    {
        $this->get("/events/{$this->event->id}/visitors.csv")->assertRedirect('/login');
    }

    public function test_another_agents_event_is_a_404(): void
    {
        $other = Agent::factory()->create();

        $this->actingAs($other)->get("/events/{$this->event->id}/visitors.csv")->assertNotFound();
    }

    public function test_export_has_header_bom_filename_and_consent_columns(): void
    {
        $v = $this->visitor([
            'email' => 'priya@example.com', 'working_with_agent' => false, 'pre_approved' => true,
            'buying_timeline' => '1_3_months', 'source' => 'paper',
        ]);
        $this->consent($v);

        $response = $this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv");

        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=scanin-pinehurst-visitors.csv');
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);

        $csv = ltrim($body, "\xEF\xBB\xBF");
        $header = str_getcsv(strtok($csv, "\n"));
        $this->assertSame('Name', $header[0]);
        $this->assertContains('Marketing consent wording', $header);

        $row = $this->rows($body)[0];
        $this->assertSame(
            ['Priya Sharma', '+19055550111', 'priya@example.com', 'no', '1-3 months', 'yes'],
            array_slice($row, 0, 6),
        );
        $this->assertSame('paper', $row[7]);
        $this->assertSame('yes', $row[8]);            // texting consent
        $this->assertSame('v-test', $row[10]);        // wording version
        $this->assertSame('no', $row[11]);            // no marketing consent
        $this->assertSame('', $row[13]);
        $this->assertSame('no', $row[14]);            // not opted out
    }

    public function test_revoked_and_opted_out_are_reported_honestly(): void
    {
        $a = $this->visitor(['full_name' => 'Revoked Rita', 'phone_e164' => '+19055550222']);
        $this->consent($a, 'checkin', ['revoked_at' => now()]);

        $b = $this->visitor(['full_name' => 'Stopped Sam', 'phone_e164' => '+19055550333', 'sms_opt_out' => true]);
        $this->consent($b);

        $rows = collect($this->rows($this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv")->streamedContent()))
            ->keyBy(0);

        $this->assertSame('no', $rows['Revoked Rita'][8]);
        $this->assertSame('yes', $rows['Stopped Sam'][14]);
    }

    public function test_anonymized_visitors_are_not_exported(): void
    {
        $this->visitor(['full_name' => 'Kept Kim', 'phone_e164' => '+19055550444']);
        $this->visitor(['full_name' => '', 'phone_e164' => '', 'anonymized_at' => now()]);

        $rows = $this->rows($this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv")->streamedContent());

        $this->assertCount(1, $rows);
        $this->assertSame('Kept Kim', $rows[0][0]);
    }

    public function test_marketing_filter_keeps_only_people_with_marketing_consent(): void
    {
        $yes = $this->visitor(['full_name' => 'Yes Yolanda', 'phone_e164' => '+19055550555']);
        $this->consent($yes, 'marketing');
        $revoked = $this->visitor(['full_name' => 'Revoked Rob', 'phone_e164' => '+19055550666']);
        $this->consent($revoked, 'marketing', ['revoked_at' => now()]);
        $this->visitor(['full_name' => 'Text Only Tom', 'phone_e164' => '+19055550777']);

        $response = $this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv?marketing=1");
        $response->assertHeader('content-disposition', 'attachment; filename=scanin-pinehurst-marketing-consent-visitors.csv');

        $rows = $this->rows($response->streamedContent());
        $this->assertCount(1, $rows);
        $this->assertSame('Yes Yolanda', $rows[0][0]);
        $this->assertSame('yes', $rows[0][11]);
        $this->assertSame('v-test', $rows[0][13]);
    }

    public function test_formula_injection_is_neutralised(): void
    {
        $this->visitor(['full_name' => '=HYPERLINK("http://evil.example","x")', 'email' => '@cmd', 'phone_e164' => '+19055550888']);

        $rows = $this->rows($this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv")->streamedContent());

        $this->assertStringStartsWith("'=", $rows[0][0]);
        $this->assertStringStartsWith("'@", $rows[0][2]);
        $this->assertSame('+19055550888', $rows[0][1]); // plain phone numbers stay intact
    }

    public function test_safe_helper_cases(): void
    {
        $this->assertSame("'-1+1", VisitorExportController::safe('-1+1'));
        $this->assertSame("'+cmd", VisitorExportController::safe('+cmd'));
        $this->assertSame('+19055550111', VisitorExportController::safe('+19055550111'));
        $this->assertSame('Normal Name', VisitorExportController::safe('Normal Name'));
        $this->assertSame('', VisitorExportController::safe(''));
    }

    public function test_event_page_has_a_download_link(): void
    {
        $this->actingAs($this->agent)->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertSee("/events/{$this->event->id}/visitors.csv", false);
    }
}
