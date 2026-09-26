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
