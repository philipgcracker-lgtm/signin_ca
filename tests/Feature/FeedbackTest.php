<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Event;
use App\Models\ShortLink;
use App\Models\Visitor;
use App\Services\FeedbackLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    private Event $event;

    private Visitor $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = Agent::factory()->create([
            'full_name' => 'Derek Breton',
            'google_review_url' => 'https://g.page/r/derek-review',
        ]);
        $this->event = Event::factory()->create(['agent_id' => $this->agent->id]);
        $this->visitor = $this->makeVisitor();
    }

    private function makeVisitor(array $attrs = []): Visitor
    {
        $v = new Visitor;
        $v->forceFill(array_merge([
            'event_id' => $this->event->id,
            'full_name' => 'Priya Sharma',
            'phone_e164' => '+19055550111',
        ], $attrs))->save();

        return $v;
    }

    private function show(Visitor $v): string
    {
        return URL::temporarySignedRoute('feedback.show', now()->addDay(), ['visitor' => $v->id]);
    }

    private function store(Visitor $v): string
    {
        return URL::temporarySignedRoute('feedback.store', now()->addDay(), ['visitor' => $v->id]);
    }

    public function test_unsigned_or_tampered_links_are_refused(): void
    {
        $this->get("/f/{$this->visitor->id}")->assertForbidden();
        $this->post("/f/{$this->visitor->id}", ['score' => 5])->assertForbidden();

        $tampered = str_replace("/f/{$this->visitor->id}", '/f/'.($this->visitor->id + 1), $this->show($this->visitor));
        $this->get($tampered)->assertForbidden();

        $this->assertNull($this->visitor->fresh()->feedback_score);
    }

    public function test_expired_link_is_refused(): void
    {
        $url = URL::temporarySignedRoute('feedback.show', now()->addMinute(), ['visitor' => $this->visitor->id]);
        $this->travel(2)->minutes();

        $this->get($url)->assertForbidden();
    }

    public function test_form_renders_for_a_valid_link(): void
    {
        $this->get($this->show($this->visitor))
            ->assertOk()
            ->assertSee($this->event->shortAddress())
            ->assertSee('name="score"', false)
            ->assertSee('shared with the home');
    }

    public function test_form_mentions_the_google_review_only_when_the_agent_has_a_link(): void
    {
        $this->get($this->show($this->visitor))
            ->assertOk()
            ->assertSee('review Derek Breton on Google');

        $this->agent->update(['google_review_url' => null]);

        $this->get($this->show($this->visitor))
            ->assertOk()
            ->assertDontSee('on Google');
    }

    public function test_answer_is_saved_and_thanks_page_shows_the_google_link(): void
    {
        $response = $this->post($this->store($this->visitor), ['score' => '4', 'notes' => '  Lovely kitchen.  ']);

        $v = $this->visitor->fresh();
        $this->assertSame(4, $v->feedback_score);
        $this->assertSame('Lovely kitchen.', $v->feedback_notes);
        $this->assertNotNull($v->feedback_at);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Write a Google review')
            ->assertSee('https://g.page/r/derek-review', false)
            ->assertSee('Derek Breton');
    }

    public function test_google_link_is_shown_whatever_the_score(): void
    {
        $response = $this->post($this->store($this->visitor), ['score' => '1']);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('https://g.page/r/derek-review', false);
    }

    public function test_no_google_button_when_the_agent_has_no_link(): void
    {
        $this->agent->update(['google_review_url' => null]);
        $response = $this->post($this->store($this->visitor), ['score' => '5']);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertDontSee('Write a Google review');
    }

    public function test_second_submit_never_overwrites_the_first(): void
    {
        $this->post($this->store($this->visitor), ['score' => '5', 'notes' => 'first']);
        $this->post($this->store($this->visitor), ['score' => '1', 'notes' => 'second']);

        $v = $this->visitor->fresh();
        $this->assertSame(5, $v->feedback_score);
        $this->assertSame('first', $v->feedback_notes);
    }

    public function test_opening_the_form_after_answering_goes_to_thanks(): void
    {
        $this->post($this->store($this->visitor), ['score' => '3']);

        $this->get($this->show($this->visitor))->assertRedirect();
    }

    public function test_score_must_be_1_to_5(): void
    {
        foreach (['', '0', '6', 'abc'] as $bad) {
            $this->post($this->store($this->visitor), ['score' => $bad])->assertSessionHasErrors('score');
        }

        $this->assertNull($this->visitor->fresh()->feedback_score);
    }

    public function test_anonymized_visitor_and_cancelled_event_have_no_page(): void
    {
        $gone = $this->makeVisitor(['phone_e164' => '+19055550222', 'anonymized_at' => now()]);
        $this->get($this->show($gone))->assertNotFound();

        $this->event->update(['status' => 'cancelled']);
        $this->get($this->show($this->visitor))->assertNotFound();
    }

    public function test_notes_are_escaped_on_the_dashboard(): void
    {
        $this->visitor->forceFill([
            'feedback_score' => 2, 'feedback_notes' => '<script>alert(1)</script>', 'feedback_at' => now(),
        ])->save();

        $this->actingAs($this->agent)->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertSee('2/5')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_dashboard_summary_counts_feedback_and_average(): void
    {
        $this->visitor->forceFill(['feedback_score' => 4, 'feedback_at' => now()])->save();
        $this->makeVisitor(['phone_e164' => '+19055550333', 'feedback_score' => 5, 'feedback_at' => now()]);

        $this->actingAs($this->agent)->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertSee('2 · avg 4.5');
    }

    public function test_feedback_short_link_resolves_to_a_working_signed_form(): void
    {
        $short = FeedbackLink::shortUrl($this->visitor);
        $code = basename($short);

        $target = ShortLink::findOrFail($code)->target_url;
        $this->assertLessThan(strlen($target), strlen($short));
        $this->get($target)->assertOk()->assertSee('name="score"', false);
    }

    public function test_profile_saves_and_validates_the_google_link(): void
    {
        $this->actingAs($this->agent)
            ->patch('/profile', ['full_name' => 'Derek Breton', 'email' => $this->agent->email, 'google_review_url' => 'http://insecure.example/x'])
            ->assertSessionHasErrors('google_review_url');

        $this->actingAs($this->agent)
            ->patch('/profile', ['full_name' => 'Derek Breton', 'email' => $this->agent->email, 'google_review_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('google_review_url');

        $this->actingAs($this->agent)
            ->patch('/profile', ['full_name' => 'Derek Breton', 'email' => $this->agent->email, 'google_review_url' => 'https://g.page/r/new'])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://g.page/r/new', $this->agent->fresh()->google_review_url);
    }
}
