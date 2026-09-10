<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotGateTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(bool $enabled, bool $pro, ?string $key, bool $integrationActive = true): Tenant
    {
        static $n = 0;
        $n++;

        $tenant = Tenant::create([
            'slug' => "chat-{$n}",
            'name' => "Chat {$n}",
            'email' => "chat{$n}@example.test",
            'password' => bcrypt('secret'),
            'plan' => $pro ? 'pro' : 'trial',
            'is_active' => true,
            'trial_ends_at' => $pro ? null : now()->subYear(),
        ]);

        SiteSettings::create(['tenant_id' => $tenant->id, 'chatbot_enabled' => $enabled]);

        if ($key !== null) {
            Integration::create([
                'tenant_id' => $tenant->id,
                'integration_type' => 'ai_provider',
                'provider' => 'openai',
                'is_active' => $integrationActive,
                'config' => ['preferred' => 'openai', 'openai_key' => $key],
            ]);
        }

        return $tenant->fresh();
    }

    public function test_a_fully_configured_chatbot_is_ready(): void
    {
        $this->assertTrue($this->tenant(true, true, 'sk-test-key')->chatbotReady());
    }

    /*
     | The bug this exists for. The widget rendered on the checkbox alone, so a visitor could
     | open the bubble on an agent's site and be told "Chatbot is not configured yet".
     */
    public function test_enabled_without_a_key_is_not_ready(): void
    {
        $this->assertFalse($this->tenant(true, true, null)->chatbotReady());
    }

    public function test_an_empty_key_counts_as_no_key(): void
    {
        $this->assertFalse($this->tenant(true, true, '')->chatbotReady());
    }

    /** Configuration drifts: a key that is present but switched off is still not usable. */
    public function test_an_inactive_integration_is_not_ready(): void
    {
        $this->assertFalse($this->tenant(true, true, 'sk-test-key', integrationActive: false)->chatbotReady());
    }

    /*
     | Worse than the missing-key case: the API answers "available on the Pro plan, please
     | upgrade", which tells the agent's own prospective buyers about their billing.
     */
    public function test_a_non_pro_tenant_is_not_ready_even_with_a_key(): void
    {
        $this->assertFalse($this->tenant(true, false, 'sk-test-key')->chatbotReady());
    }

    public function test_switched_off_is_not_ready_however_well_configured(): void
    {
        $this->assertFalse($this->tenant(false, true, 'sk-test-key')->chatbotReady());
    }

    /** The common case must not pay for an integration lookup. */
    public function test_a_disabled_chatbot_short_circuits_before_the_key_lookup(): void
    {
        $tenant = $this->tenant(false, true, null);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $tenant->chatbotReady();
        $queries = \Illuminate\Support\Facades\DB::getQueryLog();

        $this->assertLessThanOrEqual(1, count($queries), 'a switched-off chatbot should not look up integrations');
    }

    /** The standalone chat page is reachable by URL whether or not anything links to it. */
    public function test_the_chat_page_redirects_when_the_chatbot_is_not_ready(): void
    {
        $tenant = $this->tenant(true, true, null);

        $this->get("/{$tenant->slug}/chat")->assertRedirect("/{$tenant->slug}/contact");
    }

    // ── hasAiProvider(): the single definition of "a provider we can actually use" ──────
    //
    // chatbotReady() gates the public widget with it, and the settings screen gates the
    // chatbot switch with it. They must agree, so the rules are pinned here directly.

    public function test_no_integration_means_no_provider(): void
    {
        $this->assertFalse($this->tenant(false, true, null)->hasAiProvider());
    }

    public function test_an_active_key_is_a_provider(): void
    {
        $this->assertTrue($this->tenant(false, true, 'sk-test-key')->hasAiProvider());
    }

    public function test_a_switched_off_integration_is_not_a_provider(): void
    {
        $this->assertFalse($this->tenant(false, true, 'sk-test-key', integrationActive: false)->hasAiProvider());
    }

    public function test_an_empty_key_is_not_a_provider(): void
    {
        $this->assertFalse($this->tenant(false, true, '')->hasAiProvider());
    }

    /** Not plan-gated: the provider also powers the admin assistant, so this ignores Pro. */
    public function test_a_non_pro_tenant_can_still_have_a_provider(): void
    {
        $this->assertTrue($this->tenant(false, false, 'sk-test-key')->hasAiProvider());
    }

    // ── the settings screen ────────────────────────────────────────────────────────────

    private function actAsAdminOf(Tenant $tenant): User
    {
        $user = User::create([
            'first_name' => 'Ada', 'last_name' => 'Admin',
            'email' => "admin-{$tenant->id}@example.test", 'password' => bcrypt('secret'),
            'tenant_id' => $tenant->id, 'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    /*
     | With no provider the switch is unavailable, which is what replaced the old warning
     | banner. A disabled checkbox submits nothing and the controller reads the field with
     | boolean(), so the hidden companion has to carry the stored 1 — otherwise merely
     | opening the settings page and saving would quietly switch the chatbot off.
     */
    public function test_without_a_provider_the_chatbot_switch_is_disabled_and_preserves_the_stored_choice(): void
    {
        $tenant = $this->tenant(true, true, null);
        $this->actAsAdminOf($tenant);

        $html = $this->get("/{$tenant->slug}/admin/settings?tab=chatbot")->assertOk()->getContent();

        $this->assertStringContainsString('<input type="hidden" name="chatbot_enabled" value="1">', $html);
        $this->assertMatchesRegularExpression(
            '/name="chatbot_enabled" value="1"[^>]*\bdisabled\b/',
            $html,
            'the chatbot checkbox should be disabled when there is no provider'
        );
    }

    /** With a provider present the switch is operable, so no preserve-value is needed. */
    public function test_with_a_provider_the_chatbot_switch_is_operable(): void
    {
        $tenant = $this->tenant(true, true, 'sk-test-key');
        $this->actAsAdminOf($tenant);

        $html = $this->get("/{$tenant->slug}/admin/settings?tab=chatbot")->assertOk()->getContent();

        $this->assertStringContainsString('<input type="hidden" name="chatbot_enabled" value="0">', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/name="chatbot_enabled" value="1"[^>]*\bdisabled\b/',
            $html
        );
    }

    /*
     | The round trip the hidden companion exists for. The chatbot_enabled value is taken
     | from the rendered form rather than from the database, because that is the whole
     | point: the disabled checkbox submits nothing, so whatever the hidden input carries
     | is what the controller sees.
     */
    public function test_saving_settings_without_a_provider_does_not_clear_the_chatbot_preference(): void
    {
        $tenant = $this->tenant(true, true, null);
        $user = $this->actAsAdminOf($tenant);

        $html = $this->get("/{$tenant->slug}/admin/settings?tab=chatbot")->assertOk()->getContent();

        preg_match('/<input type="hidden" name="chatbot_enabled" value="(\d)">/', $html, $m);
        $this->assertNotEmpty($m, 'the settings form should carry a chatbot_enabled hidden input');

        $settings = SiteSettings::where('tenant_id', $tenant->id)->firstOrFail();

        // Raw attributes, so JSON columns arrive as the strings the form posts.
        $payload = collect($settings->getAttributes())
            ->reject(fn ($v, $k) => in_array($k, ['id', 'tenant_id', 'created_at', 'updated_at'], true))
            ->reject(fn ($v) => is_null($v))
            ->all();

        // The checkbox is disabled, so the browser sends only the hidden field.
        $payload['chatbot_enabled'] = $m[1];

        // The one form covers the profile tab too, and update() validates those.
        $payload += [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ];

        $this->post("/{$tenant->slug}/admin/settings", $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            (bool) SiteSettings::where('tenant_id', $tenant->id)->value('chatbot_enabled'),
            'losing the provider key must not clear the agent\'s chatbot preference'
        );
    }

    /** The tab moved to the "AI" label; the ?tab=chatbot key stays so old links still work. */
    public function test_the_ai_tab_is_still_reachable_by_its_old_key(): void
    {
        $tenant = $this->tenant(true, true, 'sk-test-key');
        $this->actAsAdminOf($tenant);

        $this->get("/{$tenant->slug}/admin/settings?tab=chatbot")
            ->assertOk()
            ->assertSee('AI Provider');
    }
}
