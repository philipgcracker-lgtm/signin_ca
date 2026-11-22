<?php

namespace Tests\Feature;

use App\Models\ConsentText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignInSheetTest extends TestCase
{
    use RefreshDatabase;

    private function removePaperWording(): void
    {
        ConsentText::query()->where('version', 'like', 'paper-%')->delete();
    }

    public function test_sheet_renders_paper_wording_from_the_database_with_blanks(): void
    {
        $this->removePaperWording();
        ConsentText::factory()->create([
            'version' => 'paper-v-test',
            'body' => 'Tick to hear from {agent_name}, {brokerage} by text.',
        ]);

        $this->get('/open-house-sign-in-sheet')->assertOk()
            ->assertSee('<h1>Free printable open house sign-in sheet</h1>', false)
            ->assertSee('Tick to hear from ____________________, ____________________ by text.')
            ->assertSee('rel="canonical"', false)
            ->assertSee('name="description"', false)
            ->assertDontSee('{agent_name}', false);
    }

    public function test_sheet_never_prints_online_wording(): void
    {
        $this->removePaperWording();
        ConsentText::factory()->create(['version' => 'v-online', 'body' => 'ONLINE ONLY WORDING']);

        $this->get('/open-house-sign-in-sheet')->assertNotFound();
    }

    public function test_sheet_is_not_found_and_not_in_sitemap_or_home_without_paper_wording(): void
    {
        $this->removePaperWording();

        $this->get('/open-house-sign-in-sheet')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/open-house-sign-in-sheet', false);
        $this->get('/')->assertOk()->assertDontSee('/open-house-sign-in-sheet', false);
    }

    public function test_sheet_is_in_sitemap_and_linked_from_home_when_wording_exists(): void
    {
        $this->removePaperWording();
        ConsentText::factory()->create(['version' => 'paper-v-test']);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/open-house-sign-in-sheet', false);
        $this->get('/')->assertOk()->assertSee('/open-house-sign-in-sheet', false);
    }
}
