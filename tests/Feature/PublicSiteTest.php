<?php

namespace Tests\Feature;

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
     * The property page used to declare @section('hide_header'), so it rendered with no
     * site navigation and no way back. It now takes the sticky default header, the same
     * one the gallery and map are forced to — hero mode's fixed transparent bar would
     * sit on top of the photo carousel rather than above it.
     */
    public function test_a_property_page_carries_the_sticky_site_header(): void
    {
        $tenant = $this->makeTenant([], ['header_mode' => 'hero']);
        $property = $this->makeProperty($tenant);

        $response = $this->get("/{$tenant->slug}/property/{$property->id}");

        $response->assertOk()
            ->assertSee('id="tenant-default-header"', false)
            ->assertDontSee('id="tenant-hero-header"', false)
            ->assertSee("/{$tenant->slug}/gallery", false);
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
}
