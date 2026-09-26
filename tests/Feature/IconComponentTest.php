<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | 470 inline <svg> elements were written out by hand across the views, 221 of them distinct
 | shapes. The envelope alone appeared 16 times with 12 different class strings, which is why
 | the class has to stay per-use and everything else can be shared.
 |
 | These tests pin the contract the extraction relies on: the stored element is rendered
 | verbatim, the class passes through, and an unknown name is silently nothing rather than an
 | exception on a live page.
 */
class IconComponentTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_every_icon_the_views_ask_for_is_defined(): void
    {
        $used = [];
        foreach (glob(resource_path('views/**/*.blade.php')) + glob(resource_path('views/**/**/*.blade.php')) + glob(resource_path('views/*.blade.php')) as $file) {
            preg_match_all('/<x-icon name="([^"]+)"/', file_get_contents($file), $m);
            $used = array_merge($used, $m[1]);
        }

        $defined = array_keys(config('icons'));

        $this->assertNotEmpty($used, 'no icon component uses found — has the glob broken?');
        $this->assertSame([], array_values(array_diff(array_unique($used), $defined)));
    }

    public function test_an_icon_renders_the_stored_element(): void
    {
        $html = Blade::render('<x-icon name="envelope" />');

        $icon = config('icons.envelope');
        $this->assertStringContainsString($icon['attrs'], $html);
        $this->assertStringContainsString($icon['inner'], $html);
        $this->assertStringStartsWith('<svg', trim($html));
    }

    public function test_the_class_is_passed_through_untouched(): void
    {
        $html = Blade::render('<x-icon name="trash" class="w-4 h-4 text-red-500" />');

        $this->assertStringContainsString('class="w-4 h-4 text-red-500"', $html);
    }

    /** No class means no class attribute — the component adds no default of its own. */
    public function test_no_class_means_no_class_attribute(): void
    {
        $html = Blade::render('<x-icon name="trash" />');

        $this->assertStringNotContainsString('class=', $html);
    }

    public function test_an_unknown_name_renders_nothing_rather_than_throwing(): void
    {
        $this->assertSame('', trim(Blade::render('<x-icon name="not-a-real-icon" />')));
    }

    public function test_the_icons_still_reach_the_pages_that_use_them(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        $html = $this->get("/{$tenant->slug}")->assertOk()->content();

        // The public site draws its nav and cards from these.
        $this->assertStringContainsString(config('icons.envelope')['inner'], $html);
    }
}
