<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * script-src no longer carries 'unsafe-inline'. Three inline scripts remain -- the
 * pre-paint theme block in the tenant/admin layouts (which share one), the auth layout
 * and the marketing layout -- and they are allowed by SHA-256 hash. The super-admin
 * layout has none at all, which is asserted below rather than assumed.
 *
 * A hash is exact. Reindent one of those blocks, add a comment, change a variable name,
 * and the browser silently refuses to run it: no error anyone would notice, just the
 * wrong theme for a frame on every page load, for everyone. These tests fail instead.
 */
class ContentSecurityPolicyTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function scriptSrc(): string
    {
        $htaccess = file_get_contents(public_path('.htaccess'));
        preg_match('/script-src ([^;]*)/', $htaccess, $m);

        $this->assertNotEmpty($m[1] ?? '', 'no script-src in public/.htaccess');

        return $m[1];
    }

    /** Every inline executable script in some HTML, hashed the way a browser would. */
    private function hashesIn(string $html): array
    {
        preg_match_all('/<script(?![^>]*\ssrc=)([^>]*)>(.*?)<\/script>/s', $html, $m, PREG_SET_ORDER);

        $hashes = [];
        foreach ($m as [, $attrs, $body]) {
            // application/ld+json is a data block: not executed, not covered by script-src.
            if (preg_match('/type=["\']([^"\']+)/', $attrs, $t) && $t[1] !== 'text/javascript') {
                continue;
            }
            if (trim($body) === '') {
                continue;
            }
            $hashes[] = 'sha256-'.base64_encode(hash('sha256', $body, true));
        }

        return $hashes;
    }

    public function test_script_src_does_not_allow_inline_scripts(): void
    {
        $this->assertStringNotContainsString("'unsafe-inline'", $this->scriptSrc(),
            "script-src allows inline scripts again, which is what this phase removed");
    }

    public static function layoutProvider(): array
    {
        return [
            'tenant' => ['tenant'],
            'admin' => ['admin'],
            'auth' => ['auth'],
            'marketing' => ['marketing'],
        ];
    }

    /** @dataProvider layoutProvider */
    public function test_every_inline_script_a_layout_renders_is_allowed_by_hash(string $layout): void
    {
        $html = $this->htmlFor($layout);
        $allowed = $this->scriptSrc();
        $hashes = $this->hashesIn($html);

        $this->assertNotEmpty($hashes, "the {$layout} layout rendered no inline script; if that is deliberate, drop its hash");

        foreach ($hashes as $hash) {
            $this->assertStringContainsString($hash, $allowed,
                "the {$layout} layout renders an inline script the CSP will refuse to run:\n  '{$hash}'\n"
                ."Add it to script-src in public/.htaccess, or move the script into a bundle.");
        }
    }

    /**
     * It never had a pre-paint theme block, so there is no hash for it and there must
     * never be one -- an inline script added here would simply be refused.
     */
    public function test_the_super_admin_layout_renders_no_inline_script(): void
    {
        $html = $this->actingAs($this->makeSuperAdmin())->get('/super-admin')->assertOk()->getContent();

        $this->assertSame([], $this->hashesIn($html),
            'the super-admin layout gained an inline script; the CSP will refuse to run it');
    }

    public static function standalonePageProvider(): array
    {
        return [
            'registrations closed' => ['auth.registrations-closed', []],
            'tenant locked' => ['tenant.locked', ['message' => 'Back shortly.']],
        ];
    }

    /**
     * Two pages render without a layout, so they carry their own copy of the pre-paint
     * theme block. It is byte-identical to the tenant layout's deliberately: that is what
     * keeps it covered by a hash that already exists rather than needing a fourth one.
     * Reindent it here and the browser silently refuses it, and the page is stuck light.
     *
     * @dataProvider standalonePageProvider
     */
    public function test_every_inline_script_a_standalone_page_renders_is_allowed_by_hash(string $view, array $data): void
    {
        $html = view($view, $data)->render();
        $hashes = $this->hashesIn($html);

        $this->assertNotEmpty($hashes,
            "{$view} renders no inline theme script, so it cannot set the dark class before the first paint");

        foreach ($hashes as $hash) {
            $this->assertStringContainsString($hash, $this->scriptSrc(),
                "{$view} renders an inline script the CSP will refuse to run:\n  '{$hash}'\n"
                .'Keep it byte-identical to the tenant layout\'s block, or add this hash to script-src.');
        }
    }

    private function htmlFor(string $layout): string
    {
        $tenant = $this->makeTenant();

        return match ($layout) {
            'tenant' => $this->get("/{$tenant->slug}")->assertOk()->getContent(),
            'admin' => $this->actingAs($this->makeAdmin($tenant))->get("/{$tenant->slug}/admin")->assertOk()->getContent(),
            'auth' => $this->get('/login')->assertOk()->getContent(),
            'marketing' => $this->get('/')->assertOk()->getContent(),
        };
    }
}
