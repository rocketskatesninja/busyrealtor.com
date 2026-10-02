<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
 | Dark mode is an override sheet of !important rules, and it existed in six places: the shared
 | partial that the tenant and admin layouts include, plus inline copies in marketing,
 | super-admin and auth, and extra rules of their own in admin and tenant.
 |
 | The copies had drifted, so the same utility class resolved to a different colour depending
 | on which layout you were in — `.dark .text-gray-400` was #94a3b8 in the shared sheet and
 | #475569 in marketing and super-admin, which is barely readable on a dark background. Twelve
 | selectors disagreed like that.
 |
 | This reads the stylesheets rather than a rendered page, because that is where the fault lives
 | and because it catches the next copy before anyone sees it.
 */
class DarkModeConsistencyTest extends TestCase
{
    private const SOURCES = [
        'partial' => 'views/partials/dark-mode-styles.blade.php',
        'admin' => 'views/layouts/admin.blade.php',
        'tenant' => 'views/layouts/tenant.blade.php',
        'marketing' => 'views/layouts/marketing.blade.php',
        'super-admin' => 'views/layouts/super-admin.blade.php',
        'auth' => 'views/layouts/auth.blade.php',
    ];

    /** @return array<string, array<string, string>> selector => [source => declarations] */
    private function darkRules(): array
    {
        $rules = [];

        foreach (self::SOURCES as $source => $relative) {
            $css = file_get_contents(resource_path($relative));

            // A selector capture runs from the previous closing brace, so a comment sitting
            // above a rule used to be absorbed into its key — which made that rule unique to
            // its source and quietly exempt from every comparison below.
            $css = preg_replace('#/\*.*?\*/#s', '', $css);

            preg_match_all('/([^{}]*\.dark[^{}]*)\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $selector = trim(preg_replace('/\s+/', ' ', $match[1]));
                $declarations = rtrim(trim(preg_replace('/\s+/', ' ', $match[2])), ';');
                $rules[$selector][$source] = $declarations;
            }
        }

        return $rules;
    }

    /**
     * Precedence is compared separately: one selector group legitimately differs only by
     * !important, and forcing that everywhere would override `dark:` utilities in the markup
     * of two layouts — a change with no colour benefit. Values are what must agree.
     */
    private function withoutImportant(string $declarations): string
    {
        return trim(str_replace('!important', '', $declarations));
    }

    public function test_a_dark_mode_class_means_the_same_thing_in_every_layout(): void
    {
        $disagreements = [];

        foreach ($this->darkRules() as $selector => $bySource) {
            $values = array_unique(array_map([$this, 'withoutImportant'], $bySource));

            if (count($values) > 1) {
                $disagreements[$selector] = $bySource;
            }
        }

        $this->assertSame([], $disagreements, "these selectors resolve differently per layout:\n".
            implode("\n", array_map(
                fn ($sel, $by) => "  {$sel}\n".implode("\n", array_map(
                    fn ($src, $decl) => "     {$src}: {$decl}", array_keys($by), $by
                )),
                array_keys($disagreements), $disagreements
            )));
    }

    /** The sheet only works because it outranks the dark: utilities in the markup. */
    public function test_the_shared_sheet_is_still_an_override_sheet(): void
    {
        $css = file_get_contents(resource_path(self::SOURCES['partial']));

        $rules = preg_match_all('/\.dark[^{}]*\{[^}]*\}/s', $css);
        $important = substr_count($css, '!important');

        $this->assertGreaterThan(50, $rules);
        $this->assertGreaterThan($rules * 0.8, $important, 'most rules here need !important to win');
    }

    /**
     * A status chip is two classes, and dark mode has to handle both or the chip becomes
     * unreadable in one direction or the other. Off Market shipped with .dark
     * .text-orange-700 lightening its text but no .dark .bg-orange-100, so light sat on
     * near-white at 1.5:1; Pending had the mirror image, dark amber text on a darkened
     * chip at 2.8:1. Every pair the listing-status map can produce is checked here.
     */
    /**
     * The status-chip test below pins six known pairs. This one is the general rule it is
     * a case of: wherever a view puts dark text on a light chip of the same colour, dark
     * mode has to handle both halves or the element becomes unreadable in one direction.
     *
     * Two ways to satisfy it — a dark: variant on the element, or a rule in one of the
     * override sheets. Both are in use here, deliberately: a global rule is !important
     * and would overwrite the dark colours some callouts set for themselves, so a colour
     * only gets one when nothing is already handling it locally.
     *
     * Found: the AI warning box shipped as bg-amber-50 + text-amber-800 with no dark
     * handling at all, which rendered as a glaring near-white slab on a dark page with
     * the amber signal gone. The same scan then turned up text-red-700, which never got
     * the treatment text-red-600 has, on chips that *are* darkened.
     */
    public function test_no_view_pairs_a_light_chip_with_dark_text_unhandled_in_dark_mode(): void
    {
        $sheets = '';
        foreach (self::SOURCES as $relative) {
            $sheets .= file_get_contents(resource_path($relative));
        }

        $hasRule = fn (string $class) => (bool) preg_match('/\.dark\s+\.'.preg_quote($class, '/').'\s*[,{]/', $sheets);

        $unhandled = [];

        foreach (glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) as $file) {
            preg_match_all('/class\s*=\s*"([^"]*)"/', file_get_contents($file), $matches);

            foreach ($matches[1] as $classes) {
                if (! preg_match('/(?:^|\s)bg-(\w+)-(50|100)(?:\s|$)/', $classes, $bg)) {
                    continue;
                }
                if (! preg_match('/(?:^|\s)text-'.$bg[1].'-(700|800)(?:\s|$)/', $classes, $text)) {
                    continue;
                }

                $chip = "bg-{$bg[1]}-{$bg[2]}";
                $ink = "text-{$bg[1]}-{$text[1]}";

                foreach ([[$chip, 'dark:bg-'], [$ink, 'dark:text-']] as [$class, $variant]) {
                    if (! str_contains($classes, $variant) && ! $hasRule($class)) {
                        $unhandled[] = str_replace(resource_path('views').'/', '', $file)." — .{$class}";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unhandled)),
            "these put dark text on a light chip with nothing handling dark mode:\n  "
            .implode("\n  ", array_unique($unhandled)));
    }

    public function test_both_halves_of_every_status_chip_are_handled_in_dark_mode(): void
    {
        $pairs = [
            'Featured' => ['bg-blue-100', 'text-blue-700'],
            'Active' => ['bg-green-100', 'text-green-700'],
            'Pending' => ['bg-yellow-100', 'text-yellow-700'],
            'Sold' => ['bg-gray-100', 'text-gray-600'],
            'Off Market' => ['bg-orange-100', 'text-orange-700'],
            'Withdrawn' => ['bg-red-100', 'text-red-600'],
        ];

        $rules = $this->darkRules();

        foreach ($pairs as $label => $classes) {
            foreach ($classes as $class) {
                $this->assertArrayHasKey(".dark .{$class}", $rules,
                    "the {$label} chip uses .{$class}, which has no dark mode override — "
                    .'one half of a chip being overridden and the other not is what makes it unreadable');
            }
        }
    }

    public function test_secondary_text_stays_readable_on_a_dark_background(): void
    {
        foreach ($this->darkRules() as $selector => $bySource) {
            if ($selector === '.dark .text-gray-400') {
                foreach ($bySource as $source => $declarations) {
                    $this->assertStringContainsString('#94a3b8', $declarations,
                        "{$source} sets .text-gray-400 to something other than the readable value");
                }

                return;
            }
        }

        $this->fail('.dark .text-gray-400 is no longer defined anywhere — has the sheet moved?');
    }
}
