<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The font picker and the generated faces have to be changed together. Nothing in the app
 * fails loudly if they drift: the picker just offers a font, the tenant picks it, and their
 * site quietly renders in sans-serif with no error anywhere.
 */
class SelfHostedFontsTest extends TestCase
{
    public function test_every_font_the_picker_offers_has_a_self_hosted_face(): void
    {
        foreach ($this->offeredFonts() as $font) {
            $this->assertStringContainsString(
                "font-family: '{$font}';",
                $this->fontsCss(),
                "the picker offers {$font} but no @font-face exists for it -- re-run scripts/fetch-title-fonts.py"
            );
        }
    }

    public function test_every_face_points_at_a_file_that_exists(): void
    {
        preg_match_all("/url\('\/fonts\/([^']+)'\)/", $this->fontsCss(), $matches);

        $this->assertNotEmpty($matches[1], 'no font files referenced at all');

        foreach (array_unique($matches[1]) as $file) {
            $this->assertFileExists(public_path('fonts/'.$file));
        }
    }

    public function test_no_page_asks_a_third_party_for_a_font(): void
    {
        // Self-hosting is not only about speed: a font fetched from Google announces every
        // visitor to a realtor's site to Google. The CSP is what keeps it that way.
        foreach (glob(resource_path('views/layouts/*.blade.php')) as $layout) {
            $this->assertStringNotContainsString('fonts.googleapis.com', (string) file_get_contents($layout), basename($layout));
            $this->assertStringNotContainsString('fonts.gstatic.com', (string) file_get_contents($layout), basename($layout));
        }

        $htaccess = (string) file_get_contents(public_path('.htaccess'));
        $this->assertMatchesRegularExpression("/font-src 'self' data:;/", $htaccess, 'the CSP still permits a third-party font origin');
    }

    /** @return list<string> */
    private function offeredFonts(): array
    {
        $view = (string) file_get_contents(resource_path('views/tenant/admin/settings/index.blade.php'));

        $this->assertSame(1, preg_match('/\$fontGroups = \[(.*?)\n\s*\];/s', $view, $block), 'could not find the picker list');

        // The group labels are the array keys, so they are the ones followed by =>.
        preg_match_all("/'([^']+)'(\s*=>)?/", $block[1], $found, PREG_SET_ORDER);

        return array_values(array_filter(array_map(
            fn ($m) => ($m[2] ?? '') === '' ? $m[1] : null,
            $found
        )));
    }

    private function fontsCss(): string
    {
        $path = resource_path('css/fonts.css');
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
