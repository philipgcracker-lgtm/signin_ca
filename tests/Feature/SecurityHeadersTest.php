<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_sent_and_hsts_only_over_https(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://scanin.test/')->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_home_page_has_main_landmark_skip_link_and_share_tags(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<main>', false)
            ->assertSee('href="#content"', false)
            ->assertSee('property="og:title"', false);
    }

    public function test_login_pages_are_noindex_with_own_titles_and_legal_pages_have_descriptions(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="robots" content="noindex"', false)->assertSee('<title>Log in |', false);
        $this->get('/forgot-password')->assertOk()->assertSee('<title>Reset your password |', false);
        $this->get('/privacy')->assertOk()->assertSee('name="description"', false);
        $this->get('/terms')->assertOk()->assertSee('name="description"', false);
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/privacy', false)->assertSee('/terms', false)
            ->assertDontSee('/login', false);
    }
}
