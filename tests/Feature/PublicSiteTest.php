<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\PropertyView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Characterization tests for the public tenant site — the pages a realtor's visitors
 | actually see, and the busiest thing this app serves.
 |
 | These assert what the app does *today*, deliberately, because the views behind them
 | (tenant/home, gallery, property, map, and the 66KB tenant layout) are about to be broken
 | into components. A refactor that changes any of these answers has changed behaviour, and
 | that is the whole point of writing them down first. They are not a wish list.
 |
 | The one exception to "characterize, don't judge" is tenant isolation. That is asserted
 | because it must never change, in either direction.
 */
class PublicSiteTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The property page fetches Google Places inline on a cache miss. Nothing in a test
        // should reach the network; faking it here also documents that coupling, which
        // Phase 1e moves to a queue.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    public function test_the_tenant_home_page_renders_with_its_own_branding(): void
    {
        $tenant = $this->makeTenant(['slug' => 'seaside'], ['site_title' => 'Seaside Realty']);

        $this->get("/{$tenant->slug}")
            ->assertOk()
            ->assertSee('Seaside Realty', false);
    }

    public function test_the_gallery_lists_this_tenants_active_listings(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant, ['title' => 'Marsh View Bungalow']);

        $this->get("/{$tenant->slug}/gallery")
            ->assertOk()
            ->assertSee('Marsh View Bungalow', false);
    }

    /** The isolation guarantee. 11 models lean on a global scope that had no test at all. */
    public function test_one_tenants_public_pages_never_show_another_tenants_listings(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);

        $this->makeProperty($mine, ['title' => 'My Own Listing']);
        $this->makeProperty($theirs, ['title' => 'Somebody Elses Listing']);

        foreach (['', '/gallery', '/map'] as $path) {
            $this->get("/{$mine->slug}{$path}")
                ->assertOk()
                ->assertDontSee('Somebody Elses Listing', false);
        }

        $this->get("/{$mine->slug}/api/properties")
            ->assertOk()
            ->assertDontSee('Somebody Elses Listing', false);
    }

    /**
     * The global scope on its own, with no controller and no explicit filter.
     *
     * Both this and the HTTP isolation test above were mutation-checked: with the
     * `where(tenant_id)` line deleted from `BelongsToTenant`, both fail. That was worth
     * proving, because controllers add ~120 explicit tenant filters belt-and-braces, so it
     * would be reasonable to assume the HTTP tests could not see the scope at all. They can —
     * `PropertyApiController` is the one tenant-data query in the app that carries no explicit
     * filter, so `/{slug}/api/properties` leaks the moment the scope stops working.
     *
     * This test exists separately because it holds the scope to its promise directly, without
     * depending on that one controller staying unfiltered.
     */
    public function test_the_global_scope_alone_hides_other_tenants_rows(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);

        $this->makeProperty($mine, ['title' => 'Mine']);
        $this->makeProperty($theirs, ['title' => 'Theirs']);
        $this->makeStaff($mine, ['name' => 'My Agent']);
        $this->makeStaff($theirs, ['name' => 'Their Agent']);

        app()->instance('tenant', $mine);

        $this->assertSame(['Mine'], \App\Models\Property::pluck('title')->all());
        $this->assertSame(['My Agent'], \App\Models\StaffMember::pluck('name')->all());

        // And it stamps tenant_id on create without being told.
        $created = \App\Models\Property::create(['title' => 'Stamped']);
        $this->assertSame($mine->id, $created->tenant_id);
    }

    public function test_a_property_belonging_to_another_tenant_is_not_reachable_under_this_slug(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $foreign = $this->makeProperty($theirs, ['title' => 'Not Yours']);

        $this->get("/{$mine->slug}/property/{$foreign->id}")->assertNotFound();
    }

    /**
     * Every page must begin with the doctype.
     *
     * property.blade.php and map.blade.php each opened with a <style> block placed *above*
     * @extends. Blade compiles @extends into a footer append, so the child's stray output was
     * echoed first and the response began with "<style>". Any token before the doctype puts
     * the document in quirks mode, which changes box sizing, line-height and percentage
     * heights — on the two most layout-sensitive public pages.
     */
    public function test_public_pages_begin_with_the_doctype_and_not_stray_markup(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $paths = ['', '/gallery', '/contact', '/map', "/property/{$property->id}"];

        foreach ($paths as $path) {
            $body = ltrim($this->get("/{$tenant->slug}{$path}")->assertOk()->getContent());

            $this->assertStringStartsWith(
                '<!DOCTYPE html>',
                substr($body, 0, 15),
                "/{$tenant->slug}{$path} does not start with the doctype"
            );
        }
    }

    public function test_an_unknown_tenant_slug_is_a_404(): void
    {
        $this->get('/no-such-agency')->assertNotFound();
    }

    public function test_a_property_page_renders_and_records_one_view(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant, ['title' => 'Oceanfront Estate']);

        $this->get("/{$tenant->slug}/property/{$property->id}")
            ->assertOk()
            ->assertSee('Oceanfront Estate', false);

        // Recorded per request, with no dedupe — that is current behaviour, and what makes
        // property_views the fastest-growing table on the box.
        $this->assertSame(1, PropertyView::withoutGlobalScopes()->where('property_id', $property->id)->count());
    }

    /**
     * The legal pages used to declare @section('hide_header'), so they rendered with no
     * site navigation and no way back. They take the sticky default header now, as the
     * gallery and map do: hero mode's bar is fixed and transparent, so on a wall of text
     * it would sit on top of the content rather than above it.
     *
     * The property page is deliberately not in this list. It opens on a full-bleed photo
     * carousel, where a nav bar reads as a different site from the admin area the agent
     * just came from, so it keeps hide_header and carries a breadcrumb instead.
     */
    public function test_every_public_page_but_the_homepage_carries_the_sticky_header(): void
    {
        $tenant = $this->makeTenant([], ['header_mode' => 'hero']);
        $property = $this->makeProperty($tenant);

        foreach (['/gallery', '/map', '/terms', '/privacy-policy'] as $path) {
            $this->get("/{$tenant->slug}{$path}")
                ->assertOk()
                ->assertSee('<header id="tenant-default-header"', false)
                ->assertDontSee('<header id="tenant-hero-header"', false);
        }
    }

    /**
     * Back goes to the page they actually came from, so a gallery filtered and sorted a
     * particular way is still filtered and sorted when they return. A hardcoded link to
     * the gallery would throw that away.
     */
    public function test_the_property_page_has_no_header_and_a_breadcrumb_back(): void
    {
        $tenant = $this->makeTenant([], ['header_mode' => 'hero']);
        $property = $this->makeProperty($tenant);

        $response = $this->get("/{$tenant->slug}/property/{$property->id}");

        $response->assertOk()
            ->assertDontSee('<header id="tenant-default-header"', false)
            ->assertDontSee('<header id="tenant-hero-header"', false)
            ->assertSee('Back to Gallery', false);
    }

    public function test_the_breadcrumb_follows_the_referring_page_and_keeps_its_query(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);
        $from = url("/{$tenant->slug}/gallery").'?sort=price_asc';

        $this->get("/{$tenant->slug}/property/{$property->id}", ['Referer' => $from])
            ->assertOk()
            ->assertSee('Back to Gallery', false)
            ->assertSee('sort=price_asc', false);

        $this->get("/{$tenant->slug}/property/{$property->id}", ['Referer' => url("/{$tenant->slug}/map")])
            ->assertOk()
            ->assertSee('Back to Map', false);
    }

    /**
     * Three referrers that must not be followed: another listing (which would bounce
     * between listings rather than going back), a neighbouring tenant whose slug happens
     * to begin with this one, and anywhere off the site entirely.
     */
    public function test_the_breadcrumb_refuses_referrers_it_should_not_follow(): void
    {
        $tenant = $this->makeTenant(['slug' => 'acme']);
        $neighbour = $this->makeTenant(['slug' => 'acme-two']);
        $property = $this->makeProperty($tenant);
        $other = $this->makeProperty($tenant);

        foreach ([
            url("/acme/property/{$other->id}"),
            url("/acme-two/gallery"),
            'https://somewhere-else.test/listings',
        ] as $referrer) {
            $response = $this->get("/acme/property/{$property->id}", ['Referer' => $referrer]);
            $response->assertOk()->assertSee('Back to Gallery', false);

            // The breadcrumb's own href, not merely the page's text: a listing's URL can
            // legitimately appear elsewhere on the page, in the similar-listings strip.
            preg_match('/<a href="([^"]*)"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*Back to/s',
                $response->getContent(), $m);
            $this->assertSame(url('/acme/gallery'), html_entity_decode($m[1] ?? ''),
                "the breadcrumb followed {$referrer}");
        }
    }

    public function test_the_homepage_keeps_the_hero_header_when_that_mode_is_set(): void
    {
        $tenant = $this->makeTenant([], ['header_mode' => 'hero']);

        $this->get("/{$tenant->slug}")
            ->assertOk()
            ->assertSee('<header id="tenant-hero-header"', false)
            ->assertDontSee('<header id="tenant-default-header"', false);
    }

    public function test_the_properties_api_returns_json_for_this_tenant_only(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant, ['title' => 'Mapped Listing']);

        $response = $this->getJson("/{$tenant->slug}/api/properties");

        $response->assertOk();
        $this->assertStringContainsString('Mapped Listing', $response->getContent());
    }

    public function test_the_contact_and_gallery_and_map_pages_all_render(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        foreach (['/contact', '/gallery', '/map'] as $path) {
            $this->get("/{$tenant->slug}{$path}")->assertOk();
        }
    }

    public function test_the_marketing_and_legal_pages_render_without_a_tenant(): void
    {
        foreach (['/', '/privacy-policy', '/terms', '/up'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_the_sitemaps_render(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        $this->get('/sitemap.xml')->assertOk();
        $this->get('/marketing-sitemap.xml')->assertOk();
        $this->get("/{$tenant->slug}/llms.txt")->assertOk();
    }

    public function test_a_preview_request_stills_the_heros_motion_effects(): void
    {
        $tenant = $this->heroTenant(['parallax' => true, 'ken_burns' => true, 'particles' => true]);

        $live = $this->get("/{$tenant->slug}")->assertOk()->getContent();
        $this->assertStringContainsString('hero-parallax', $this->heroClasses($live, 'hero-parallax'));
        $this->assertStringContainsString('hero-ken-burns', $this->heroClasses($live, 'hero-bg'));
        $this->assertStringContainsString('id="hero-particles"', $live);

        $preview = $this->get("/{$tenant->slug}?preview=1")->assertOk()->getContent();
        $this->assertStringNotContainsString('hero-parallax', $this->heroClasses($preview, 'hero-parallax'));
        $this->assertStringNotContainsString('background-attachment: fixed', $this->heroLayer($preview, 'hero-bg'));
        $this->assertStringNotContainsString('hero-ken-burns', $this->heroClasses($preview, 'hero-bg'));
        $this->assertStringNotContainsString('id="hero-particles"', $preview);
    }

    public function test_the_hero_picks_a_parallax_mechanism_that_survives_ken_burns(): void
    {
        // Parallax alone pins the background to the viewport. That cannot work once Ken
        // Burns is on, because a transformed layer becomes the containing block for its
        // own fixed background -- so the pair switches to a transform on the wrapper.
        $cases = [
            // [parallax, ken_burns] => [wrapper travels, background pinned, inner zooms]
            [[false, false], [false, false, false]],
            [[true,  false], [false, true,  false]],
            [[false, true],  [false, false, true]],
            [[true,  true],  [true,  false, true]],
        ];

        foreach ($cases as [[$parallax, $kenBurns], [$travels, $pinned, $zooms]]) {
            $tenant = $this->heroTenant(['parallax' => $parallax, 'ken_burns' => $kenBurns]);
            $html = $this->get("/{$tenant->slug}")->assertOk()->getContent();
            $label = 'parallax='.var_export($parallax, true).' ken_burns='.var_export($kenBurns, true);

            $wrapper = $this->heroClasses($html, 'hero-parallax');
            $inner = $this->heroLayer($html, 'hero-bg');

            $this->assertSame($travels, str_contains($wrapper, 'hero-parallax'), "wrapper travels, {$label}");
            $this->assertSame($pinned, str_contains($inner, 'background-attachment: fixed'), "background pinned, {$label}");
            $this->assertSame($zooms, str_contains($this->heroClasses($html, 'hero-bg'), 'hero-ken-burns'), "inner zooms, {$label}");

            // Pinning and zooming on one element is the combination that cancels itself.
            $this->assertFalse($pinned && $zooms, "mechanisms must not collide, {$label}");
        }
    }

    public function test_the_marketing_previews_ask_for_a_stilled_hero(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $frames = preg_match_all('/<iframe[^>]*src="([^"]*demo-realty[^"]*)"/', $html, $matches);
        $this->assertSame(2, $frames, 'the marketing page should embed two demo previews');

        foreach ($matches[1] as $src) {
            $this->assertStringContainsString('preview=1', $src);
        }
    }

    public function test_the_hero_never_paints_white_while_its_image_loads(): void
    {
        // The preset JPEGs are ~440 KB and every word of hero text is white. With no colour
        // painted under the image the hero is a white rectangle with invisible words on it,
        // which is indistinguishable from a page whose stylesheet failed to load.
        foreach (['preset', 'image', 'nonsense'] as $type) {
            $tenant = $this->makeTenant([], ['hero_background_type' => $type]);
            $style = $this->heroLayer($this->get("/{$tenant->slug}")->assertOk()->getContent(), 'hero-bg');

            $this->assertMatchesRegularExpression(
                '/background:\s*#[0-9a-f]{3,8}\s+url\(/i',
                $style,
                "hero_background_type={$type} should paint a colour under the image"
            );
        }
    }

    public function test_a_preview_frame_asks_for_no_cookie_consent(): void
    {
        $tenant = $this->makeTenant();

        $live = $this->get("/{$tenant->slug}")->assertOk()->getContent();
        $this->assertStringContainsString('id="cookie-banner"', $live);
        $this->assertStringContainsString('cookie-consent', $live);

        // Nothing in a preview frame can be clicked, so consent cannot be given there --
        // and a banner nobody answers still covers the bottom of the shot.
        $preview = $this->get("/{$tenant->slug}?preview=1")->assertOk()->getContent();
        $this->assertStringNotContainsString('id="cookie-banner"', $preview);
        $this->assertStringNotContainsString('cookie-consent', $preview);
        $this->assertStringNotContainsString('cookie-banner-inner', $preview);
    }

    private function heroTenant(array $effects): Tenant
    {
        return $this->makeTenant([], ['hero_effects' => $effects]);
    }

    /** A hero layer's class attribute, which is where the effect switches land -- and not its
     *  id, which contains the same words and matches whether the effect is on or off. */
    private function heroClasses(string $html, string $id): string
    {
        preg_match('/class="([^"]*)"/', $this->heroLayer($html, $id), $matches);

        return $matches[1] ?? '';
    }

    /** One hero layer's own open tag -- the effect classes live there, the stylesheet does not. */
    private function heroLayer(string $html, string $id): string
    {
        $this->assertSame(1, preg_match('/<div id="'.$id.'"[^>]*>/', $html, $matches), "#{$id} should render once");

        return $matches[0];
    }
}
