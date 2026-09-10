<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\Tenant;
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
}
