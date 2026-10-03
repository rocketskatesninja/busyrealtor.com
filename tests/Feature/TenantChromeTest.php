<?php

namespace Tests\Feature;

use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * The public layout's own scripts moved into a bundle. Two things about that are worth
 * pinning: the analytics id now travels as a meta tag rather than an interpolated global,
 * and the one block that must stay inline really does.
 */
class TenantChromeTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function inlineScripts(string $html): array
    {
        preg_match_all('/<script(?![^>]*src=)[^>]*>(.*?)<\/script>/s', $html, $m);

        return array_values(array_filter(array_map('trim', $m[1])));
    }

    /**
     * It sets the dark class before the first paint. A deferred module cannot: the page
     * would render light and then switch, which is the flash this avoids. When the CSP
     * finally drops 'unsafe-inline' this block needs a nonce or a hash — not removal.
     */
    public function test_the_pre_paint_theme_script_is_still_inline(): void
    {
        $tenant = $this->makeTenant();

        $scripts = $this->inlineScripts($this->get("/{$tenant->slug}")->assertOk()->getContent());

        $prePaint = array_filter($scripts, fn ($s) => str_contains($s, "classList.add('dark')"));
        $this->assertCount(1, $prePaint, 'the pre-paint theme script must stay inline');
    }

    /**
     * The public header says Login whoever is looking, on purpose.
     *
     * It briefly said "Dashboard" to signed-in users, to give an agent viewing their own
     * site a way back. That was solving a problem that did not exist — see the test below
     * — at the cost of showing the owner a page that differs from what a visitor sees,
     * which makes the admin area's "View Website" link a misleading preview.
     */
    public function test_the_public_header_looks_the_same_to_the_owner_as_to_a_visitor(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $asGuest = $this->get("/{$tenant->slug}")->assertOk()->getContent();
        $asOwner = $this->actingAs($admin)->get("/{$tenant->slug}")->assertOk()->getContent();

        foreach ([$asGuest, $asOwner] as $html) {
            $this->assertStringContainsString('>Login</a>', $html);
            $this->assertStringNotContainsString('>Dashboard</a>', $html);
        }
    }

    /**
     * Which is why Login is enough: an agent who clicks it is already signed in and ends
     * up on their dashboard. The link was never a dead end, it just reads like one.
     *
     * It takes two hops — the guest middleware bounces to the site root, and the root
     * sends a signed-in agent on to their own admin area — so this follows the chain
     * rather than asserting the first redirect, which lands somewhere uninteresting.
     */
    public function test_login_sends_an_already_signed_in_agent_to_their_dashboard(): void
    {
        $tenant = $this->makeTenant();

        $this->actingAs($this->makeAdmin($tenant))
            ->followingRedirects()
            ->get('/login')
            ->assertOk()
            ->assertSee('id="dashboard"', false);
    }

    public function test_the_public_chrome_is_bundled_rather_than_inline(): void
    {
        $tenant = $this->makeTenant();

        $html = $this->get("/{$tenant->slug}/gallery")->assertOk()->getContent();

        // Function definitions only: `is-scrolled` is a class name the layout's own
        // stylesheet still uses, so its presence says nothing about where the JS lives.
        foreach (['function tenantNavToggle', 'function themeToggle', 'function updateThemeIcons'] as $moved) {
            $this->assertStringNotContainsString($moved, $html, "{$moved} is inline again");
        }

        // And no on* attribute came back with it.
        $this->assertSame(0, preg_match('/\son(click|change|input|submit)=/', $html),
            'an inline event handler is back on the public layout');
    }

    public function test_analytics_travels_as_a_meta_tag_with_no_inline_script(): void
    {
        $tenant = $this->makeTenant();
        Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'google_analytics',
            'is_active' => true,
            'api_key' => 'G-TESTID123',
        ]);

        $html = $this->get("/{$tenant->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="ga-id" content="G-TESTID123">', $html);
        $this->assertStringNotContainsString('window._gaId', $html);
        $this->assertStringNotContainsString('googletagmanager', $html,
            'the tag loader belongs in the bundle, behind consent');
    }

    public function test_a_tenant_without_analytics_emits_no_meta_tag(): void
    {
        $tenant = $this->makeTenant();

        $this->get("/{$tenant->slug}")->assertOk()->assertDontSee('name="ga-id"', false);
    }

    public function test_a_bundled_page_emits_no_empty_script_element(): void
    {
        $tenant = $this->makeTenant();

        $html = $this->get("/{$tenant->slug}/gallery")->assertOk()->getContent();

        // Only the pre-paint block should be left, and nothing should be an empty shell.
        $this->assertCount(1, $this->inlineScripts($html));
        $this->assertStringNotContainsString("<script>\n</script>", $html);
    }
}
