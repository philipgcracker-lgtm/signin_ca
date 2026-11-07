<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ConsentText;
use App\Models\Event;
use App\Models\ShortLink;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingClicksTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://scanin.ca']);
        ConsentText::factory()->create(['version' => 'v-test']);
        $this->agent = Agent::factory()->create();
        $this->event = Event::factory()->create(['agent_id' => $this->agent->id, 'slug' => 'pinehurst']);
    }

    private function visitor(string $name, string $phone): Visitor
    {
        return Visitor::create(['event_id' => $this->event->id, 'full_name' => $name, 'phone_e164' => $phone]);
    }

    private function listingLink(Visitor $v, int $clicks): ShortLink
    {
        $l = ShortLink::createFor($this->event->listing_url, $this->event->id, $v->id);
        $l->forceFill(['clicks' => $clicks])->save();

        return $l;
    }

    public function test_clicks_show_per_visitor_and_in_the_summary(): void
    {
        $clicked = $this->visitor('Priya Clicked', '+19055550111');
        $quiet = $this->visitor('Quinn Quiet', '+19055550112');
        $paper = $this->visitor('Pat Paper', '+19055550113');
        $this->listingLink($clicked, 3);
        $this->listingLink($quiet, 0);

        $res = $this->actingAs($this->agent)->get("/events/{$this->event->id}")->assertOk();
        $res->assertSee('Opened listing')->assertSee('3 clicks')->assertSee('Not yet');

        $summary = $res->viewData('summary');
        $this->assertSame(1, $summary['clicked']);

        $rows = $res->viewData('rows')->keyBy(fn ($r) => $r->visitor->id);
        $this->assertSame(3, $rows[$clicked->id]->clicks);
        $this->assertTrue($rows[$quiet->id]->has_link);
        $this->assertFalse($rows[$paper->id]->has_link);
    }

    public function test_feedback_links_do_not_count_as_listing_clicks(): void
    {
        $v = $this->visitor('Fran Feedback', '+19055550114');
        $this->listingLink($v, 1);
        // A feedback short link also carries visitor_id; its clicks must not be added.
        $fb = ShortLink::createFor('https://scanin.ca/f/'.$v->id.'?signature=x', $this->event->id, $v->id);
        $fb->forceFill(['clicks' => 9])->save();

        $res = $this->actingAs($this->agent)->get("/events/{$this->event->id}")->assertOk();
        $this->assertSame(1, $res->viewData('rows')->first()->clicks);
    }

    public function test_csv_has_a_clicks_column(): void
    {
        $a = $this->visitor('Priya Clicked', '+19055550111');
        $this->visitor('Pat Paper', '+19055550113');
        $this->listingLink($a, 2);

        $csv = $this->actingAs($this->agent)->get("/events/{$this->event->id}/visitors.csv")->assertOk()->streamedContent();
        $lines = array_values(array_filter(preg_split('/\r?\n/', $csv)));

        $this->assertStringContainsString('Listing link clicks', $lines[0]);
        $this->assertStringEndsWith(',2', $lines[1]);   // had a link: count
        $this->assertStringEndsWith(',', $lines[2]);    // no link (paper, no text): blank
    }
}
