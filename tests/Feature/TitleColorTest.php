<?php

namespace Tests\Feature;

use App\Models\SiteSettings;
use Tests\TestCase;

class TitleColorTest extends TestCase
{
    /*
     | The settings screen's three colour pickers all fell back to the same flat blue, while
     | the preview above them — and both layouts that render the real title — fell back to
     | the blue/purple/navy gradient. A tenant who had never touched appearance settings was
     | shown a gradient, offered three identical swatches, and, because a colour input always
     | submits a value, saved a "gradient" with no gradient in it.
     */
    public function test_an_untouched_gradient_is_actually_a_gradient(): void
    {
        $settings = new SiteSettings;

        $stops = [
            $settings->titleColor('title_gradient_start'),
            $settings->titleColor('title_gradient_via'),
            $settings->titleColor('title_gradient_end'),
        ];

        $this->assertCount(3, array_unique($stops), 'three identical stops is not a gradient');
    }

    public function test_the_default_colour_mode_is_gradient(): void
    {
        $this->assertSame('gradient', (new SiteSettings)->titleColor('title_color_type'));
    }

    public function test_a_saved_colour_wins_over_the_default(): void
    {
        $settings = new SiteSettings(['title_gradient_via' => '#ABCDEF']);

        $this->assertSame('#ABCDEF', $settings->titleColor('title_gradient_via'));
    }

    /** A blank column is what a colour input renders as black, which nobody chose. */
    public function test_an_empty_value_falls_back_rather_than_rendering_black(): void
    {
        $settings = new SiteSettings(['title_gradient_via' => '']);

        $this->assertSame(
            SiteSettings::TITLE_DEFAULTS['title_gradient_via'],
            $settings->titleColor('title_gradient_via'),
        );
    }

    /** Every field the screen offers has a default, or the picker renders black. */
    public function test_every_title_colour_field_has_a_default(): void
    {
        $fields = [
            'title_color_type', 'title_gradient_start', 'title_gradient_via',
            'title_gradient_end', 'title_color_solid',
        ];

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, SiteSettings::TITLE_DEFAULTS, "{$field} has no default");
        }
    }
}
