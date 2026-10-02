<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Tenant;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * A tenant with a working OpenAI key and `preferred` left at anthropic had a dead
 * chatbot and a settings page reading "Saved: OpenAI key" directly above "Add an AI
 * provider above to switch this on". resolve() reading only the preferred provider's
 * key is correct and stays -- picking Anthropic must not route visitors' conversations
 * to OpenAI -- so the fix is that the screens say which it is.
 */
class AiProviderStatusTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function tenantWith(array $config, bool $active = true): Tenant
    {
        $tenant = $this->makeTenant();
        Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'ai_provider',
            'is_active' => $active,
            'config' => $config,
        ]);

        return $tenant->fresh();
    }

    public function test_a_key_for_the_preferred_provider_is_ready(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'openai', 'openai_key' => 'sk-test']);

        $this->assertTrue(AiProviderService::status($tenant)['ok']);
        $this->assertNull(AiProviderService::problem($tenant));
        $this->assertTrue($tenant->hasAiProvider());
    }

    public function test_a_key_for_the_other_provider_names_both_ways_out(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'anthropic', 'openai_key' => 'sk-test']);

        $status = AiProviderService::status($tenant);
        $this->assertFalse($status['ok']);
        $this->assertSame('no_key_for_preferred', $status['reason']);
        $this->assertSame(['openai'], $status['keyed']);

        $problem = AiProviderService::problem($tenant);
        $this->assertStringContainsString('set to Anthropic', $problem);
        $this->assertStringContainsString('switch the provider to OpenAI', $problem);
    }

    public function test_the_other_providers_key_is_never_used(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'anthropic', 'openai_key' => 'sk-test']);

        // The whole point: a key is present, and the chatbot still stays off.
        $this->assertNull(AiProviderService::resolve($tenant, activeOnly: true)['key']);
        $this->assertFalse($tenant->hasAiProvider());
    }

    public function test_no_keys_at_all_reads_differently_from_a_mismatch(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'anthropic']);

        $this->assertSame('no_keys', AiProviderService::status($tenant)['reason']);
        $this->assertStringContainsString('No AI provider key is saved', AiProviderService::problem($tenant));
    }

    public function test_a_switched_off_integration_says_so(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'openai', 'openai_key' => 'sk-test'], active: false);

        $this->assertSame('inactive', AiProviderService::status($tenant)['reason']);
        $this->assertStringContainsString('switched off', AiProviderService::problem($tenant));
    }

    public function test_the_legacy_single_key_column_counts_for_the_preferred_provider(): void
    {
        $tenant = $this->makeTenant();
        Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'ai_provider',
            'is_active' => true,
            'api_key' => 'sk-legacy',
            'config' => ['preferred' => 'openai'],
        ]);

        $this->assertTrue(AiProviderService::status($tenant->fresh())['ok']);
    }

    public function test_a_tenant_who_never_set_up_ai_is_not_warned_at_all(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $response = $this->actingAs($admin)->get("/{$tenant->slug}/admin/settings?tab=chatbot");

        // The chatbot panel still says why it is off; the amber box is for the case where
        // a key exists and cannot be used, not for an empty panel.
        $response->assertOk()
            ->assertSee('No AI provider key is saved yet.', false)
            ->assertDontSee('bg-amber-50 border border-amber-200', false);
    }

    public function test_the_settings_page_explains_the_mismatch_instead_of_asking_for_a_provider(): void
    {
        $tenant = $this->tenantWith(['preferred' => 'anthropic', 'openai_key' => 'sk-test']);
        $admin = $this->makeAdmin($tenant);

        $response = $this->actingAs($admin)->get("/{$tenant->slug}/admin/settings?tab=integrations");

        $response->assertOk()
            ->assertSee('which has no key saved', false)
            ->assertDontSee('Add an AI provider above to switch this on', false);
    }
}
