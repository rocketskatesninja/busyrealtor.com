<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | In hero mode the public header sits transparent over the hero image and turns solid once
 | you scroll. The nav and the hamburger were rendered with `opacity:0;pointer-events:none`
 | and only revealed by the scroll handler, so on the page a visitor actually lands on there
 | was no Gallery, no Map, no Login and no theme toggle — nothing until they scrolled.
 |
 | The stylesheet had white .nav-link rules for the un-scrolled header the whole time; they
 | were simply never reachable. Now the links show from the first paint, in white with the
 | same drop-shadow the logo uses so they read against a photograph.
 |
 | The transparent header only ever appears on the homepage: gallery and map force the solid
 | header, and chat, contact, legal and property hide it entirely.
 */
class HeaderNavTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_the_header_links_are_not_hidden_before_you_scroll(): void
    {
        $tenant = $this->makeTenant();

        $html = $this->get("/{$tenant->slug}")->assertOk()->content();

        preg_match('/<nav id="tenant-nav"[^>]*>/', $html, $nav);
        $this->assertNotEmpty($nav, 'the hero header nav is missing entirely');
        $this->assertStringNotContainsString('opacity:0', $nav[0]);
        $this->assertStringNotContainsString('pointer-events:none', $nav[0]);

        preg_match('/<button[^>]*id="tenant-hamburger"[^>]*>/', $html, $hamburger);
        $this->assertNotEmpty($hamburger);
        $this->assertStringNotContainsString('opacity:0', $hamburger[0]);
        $this->assertStringNotContainsString('pointer-events:none', $hamburger[0]);
    }

    public function test_the_links_themselves_are_in_the_header(): void
    {
        $tenant = $this->makeTenant();

        $html = $this->get("/{$tenant->slug}")->assertOk()->content();
        $nav = substr($html, strpos($html, '<nav id="tenant-nav"'));
        $nav = substr($nav, 0, strpos($nav, '</nav>'));

        foreach (['Gallery', 'Map', 'Login'] as $label) {
            $this->assertStringContainsString(">{$label}<", $nav, "{$label} is not in the header nav");
        }
        $this->assertStringContainsString('theme-toggle-btn', $nav);
    }

    /**
     * Without JS the scroll handler never runs, so whatever the markup says is what a visitor
     * gets. That used to be an invisible nav; it should be a usable one.
     */
    public function test_the_links_do_not_depend_on_javascript_to_appear(): void
    {
        $tenant = $this->makeTenant();

        $html = $this->get("/{$tenant->slug}")->assertOk()->content();
        preg_match('/<nav id="tenant-nav"([^>]*)>/', $html, $nav);

        $this->assertStringContainsString('drop-shadow', $nav[1],
            'the un-scrolled nav needs the shadow to read against the hero photo');
    }

    /** Gallery and map deliberately use the solid header, so they are unaffected by all this. */
    public function test_gallery_and_map_still_use_the_solid_header(): void
    {
        $tenant = $this->makeTenant();

        foreach (['gallery', 'map'] as $page) {
            $html = $this->get("/{$tenant->slug}/{$page}")->assertOk()->content();
            $this->assertStringNotContainsString('<header id="tenant-hero-header"', $html,
                "/{$page} should render the default header, not the transparent one");
        }
    }
}
