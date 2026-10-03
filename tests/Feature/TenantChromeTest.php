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
     * The public header said "Login" whether or not you were signed in, on every public
     * page, so an agent looking at their own site had no way back to their admin area.
     * Reported from the property page, but it was never specific to it.
     */
    public function test_a_signed_in_agent_gets_a_dashboard_link_not_a_login_link(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        foreach (["/{$tenant->slug}", "/{$tenant->slug}/gallery", "/{$tenant->slug}/map"] as $path) {
            $this->actingAs($admin)->get($path)
                ->assertOk()
                ->assertSee('>Dashboard</a>', false)
                ->assertSee("/{$tenant->slug}/admin", false);
        }
    }

    public function test_a_guest_still_gets_a_login_link(): void
    {
        $tenant = $this->makeTenant();

        $this->get("/{$tenant->slug}")
            ->assertOk()
            ->assertSee('>Login</a>', false)
            ->assertDontSee('>Dashboard</a>', false);
    }

    public function test_a_super_admin_is_sent_to_the_platform_console(): void
    {
        $tenant = $this->makeTenant();

        $this->actingAs($this->makeSuperAdmin())->get("/{$tenant->slug}")
            ->assertOk()
            ->assertSee('>Dashboard</a>', false)
            ->assertSee(route('super.dashboard'), false);
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
