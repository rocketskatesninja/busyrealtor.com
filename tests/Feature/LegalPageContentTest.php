<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * Legal bodies were stored HTML-escaped, so the public page printed &quot;we&quot;
 * rather than "we". A migration decoded them; these guard the two halves of that --
 * that the public page shows real punctuation, and that editing and saving the page
 * does not quietly escape it again.
 */
class LegalPageContentTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private const BODY = 'Demo Realty ("we", "us", or "our") won\'t sell your data. Terms & conditions apply.';

    private function tenantWithLegal(): array
    {
        $tenant = $this->makeTenant();

        foreach (['privacy', 'terms'] as $type) {
            LegalPage::create(['tenant_id' => $tenant->id, 'page_type' => $type, 'content' => self::BODY]);
        }

        return [$tenant, $this->makeAdmin($tenant)];
    }

    public function test_the_public_page_shows_real_punctuation_not_entity_text(): void
    {
        [$tenant] = $this->tenantWithLegal();

        foreach (['/privacy-policy', '/terms'] as $path) {
            $response = $this->get("/{$tenant->slug}{$path}");

            $response->assertOk();
            // The view escapes once on the way out, which is correct — a quote reaches the
            // browser as &quot; and renders as ". What must never appear is &amp;quot;,
            // which is what a pre-escaped body produces and what the visitor reads literally.
            $this->assertStringNotContainsString('&amp;quot;', $response->getContent());
            $this->assertStringNotContainsString('&amp;#039;', $response->getContent());
            $this->assertStringNotContainsString('&amp;amp;', $response->getContent());
        }
    }

    public function test_the_editor_round_trip_does_not_re_escape_the_body(): void
    {
        [$tenant, $admin] = $this->tenantWithLegal();
        $this->actingAs($admin);

        // What the browser would send back after the textarea is rendered and submitted
        // untouched, alongside the rest of the one form that covers every settings tab.
        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        $payload = collect($settings->getAttributes())
            ->reject(fn ($v, $k) => in_array($k, ['id', 'tenant_id', 'created_at', 'updated_at'], true))
            ->reject(fn ($v) => is_null($v))
            ->all();
        $payload += [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'privacy' => self::BODY,
            'terms' => self::BODY,
        ];

        $this->post("/{$tenant->slug}/admin/settings", $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach (['privacy', 'terms'] as $type) {
            $this->assertSame(self::BODY, LegalPage::where('tenant_id', $tenant->id)
                ->where('page_type', $type)->value('content'),
                "saving settings re-escaped the {$type} body");
        }
    }

    public function test_the_editor_renders_the_body_for_a_textarea_without_double_escaping(): void
    {
        [$tenant, $admin] = $this->tenantWithLegal();

        $response = $this->actingAs($admin)->get("/{$tenant->slug}/admin/settings");

        $response->assertOk();
        // One level of escaping inside the textarea is right: the browser decodes it back
        // to a literal quote. Two levels is the bug.
        $this->assertStringNotContainsString('&amp;quot;', $response->getContent());
    }
}
