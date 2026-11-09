<?php

namespace Tests\Feature;

use App\Models\ConsentText;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_and_terms_are_public_and_say_the_important_things(): void
    {
        $this->get('/privacy')->assertOk()
            ->assertSee('Privacy policy')
            ->assertSee('STOP')->assertSee('DELETE')
            ->assertSee('Twilio')->assertSee('United States')
            ->assertSee('Privacy Commissioner of Canada');

        $this->get('/terms')->assertOk()
            ->assertSee('Terms of service')
            ->assertSee('MLS')->assertSee('Respect opt-outs')
            ->assertSee('governed by the laws of Ontario');
    }

    public function test_the_contact_email_shows_when_configured_and_the_page_still_works_without_it(): void
    {
        config(['scanin.contact_email' => null]);
        $this->get('/privacy')->assertOk()->assertSee('contact the agent whose open house you attended');

        config(['scanin.contact_email' => 'privacy@example.com', 'scanin.mailing_address' => '1 Main St, Welland, Ontario']);
        $this->get('/privacy')->assertOk()
            ->assertSee('privacy@example.com')->assertSee('1 Main St, Welland, Ontario');
        $this->get('/terms')->assertOk()->assertSee('privacy@example.com');
    }

    public function test_the_privacy_page_points_a_visitor_to_the_agent_who_ran_their_open_house(): void
    {
        $agent = \App\Models\Agent::factory()->create(['full_name' => 'Derek Breton', 'brokerage_name' => 'Coldwell Banker', 'email' => 'derek@example.com']);
        $other = \App\Models\Agent::factory()->create(['email' => 'someone-else@example.com']);
        $event = Event::factory()->create(['agent_id' => $agent->id]);
        Event::factory()->create(['agent_id' => $other->id]);

        $this->get('/privacy?e='.$event->slug)->assertOk()
            ->assertSee('Derek Breton, Coldwell Banker')->assertSee('derek@example.com')
            ->assertDontSee('someone-else@example.com');

        // An unknown or junk slug is ignored, not an error, and leaks nothing.
        $this->get('/privacy?e=nope')->assertOk()->assertDontSee('derek@example.com');
        $this->get('/privacy?e[]=x')->assertOk()->assertDontSee('derek@example.com');
        $this->get('/privacy')->assertOk()->assertDontSee('derek@example.com');
    }

    public function test_a_cancelled_event_or_suspended_agent_is_not_exposed_through_the_privacy_link(): void
    {
        $agent = \App\Models\Agent::factory()->create(['email' => 'derek@example.com']);
        $cancelled = Event::factory()->cancelled()->create(['agent_id' => $agent->id]);
        $this->get('/privacy?e='.$cancelled->slug)->assertOk()->assertDontSee('derek@example.com');

        $suspended = \App\Models\Agent::factory()->suspended()->create(['email' => 'gone@example.com']);
        $event = Event::factory()->create(['agent_id' => $suspended->id]);
        $this->get('/privacy?e='.$event->slug)->assertOk()->assertDontSee('gone@example.com');

        $removed = \App\Models\Agent::factory()->create(['email' => 'removed@example.com']);
        $e2 = Event::factory()->create(['agent_id' => $removed->id]);
        $removed->delete();
        $this->get('/privacy?e='.$e2->slug)->assertOk()->assertDontSee('removed@example.com');
    }

    public function test_the_page_says_data_is_hosted_in_canada_and_twilio_in_the_us(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('hosted with a Canadian hosting provider')->assertDontSee('Ontario)')->assertSee('stored by Twilio in the United States');
    }

    public function test_the_page_states_the_24_month_retention_the_sweep_enforces(): void
    {
        $this->get('/privacy')->assertOk()
            ->assertSee('erased automatically 24 months after the open house ends')
            ->assertDontSee('do not yet delete');
    }

    public function test_scanins_own_contact_shows_alongside_the_agent_and_in_the_contact_section(): void
    {
        config(['scanin.contact_email' => 'privacy@scanin.ca']);
        $agent = \App\Models\Agent::factory()->create(['email' => 'derek@example.com']);
        $event = Event::factory()->create(['agent_id' => $agent->id]);

        $with = $this->get('/privacy?e='.$event->slug)->assertOk();
        $with->assertSee('derek@example.com')->assertSee('privacy@scanin.ca');

        $this->get('/privacy')->assertOk()->assertSee('privacy@scanin.ca')->assertDontSee('derek@example.com');
    }

    public function test_no_template_placeholders_are_left_on_the_pages(): void
    {
        foreach (['/privacy', '/terms'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringNotContainsString('[', strip_tags($html), "$url has a bracketed placeholder");
            $this->assertStringNotContainsString('DRAFT', $html);
        }
    }

    public function test_the_check_in_form_links_to_the_privacy_policy_and_stays_small(): void
    {
        ConsentText::factory()->create();
        $event = Event::factory()->create();

        $res = $this->get("/e/{$event->slug}")->assertOk()
            ->assertSee(route('privacy', ['e' => $event->slug]), false)->assertSee(route('terms'), false);

        $this->assertLessThan(100 * 1024, strlen($res->getContent()));
    }

    public function test_the_landing_page_and_sign_up_link_to_both(): void
    {
        $this->get('/')->assertOk()->assertSee(route('privacy'), false)->assertSee(route('terms'), false);

        config(['scanin.registration_open' => true]);
        $this->get('/register')->assertOk()->assertSee(route('privacy'), false)->assertSee(route('terms'), false);
    }
}
