<?php

namespace Tests\Feature;

use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Saving one settings tab used to switch off the social integrations configured on another.
 |
 | The settings screen is tabbed and each tab posts on its own, but the Facebook and X blocks
 | were rebuilt unconditionally from the request — and boolean() on a field that was never
 | submitted is false. So saving Appearance set is_active to false and nulled the Facebook
 | page id, with nothing to indicate it had happened. Re-entering the setup wizard had a
 | matching fault in the other direction: it hardcoded the auto-post flags back to true.
 */
class IntegrationPersistenceTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    private function configuredFacebook(\App\Models\Tenant $tenant): Integration
    {
        return Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'facebook',
            'api_key' => 'fb-token',
            'is_active' => true,
            'config' => [
                'page_id' => '1234567890',
                'post_on_new_listing' => false,
                'post_on_sold' => false,
            ],
        ]);
    }

    public function test_saving_an_unrelated_tab_leaves_facebook_alone(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $this->configuredFacebook($tenant);

        // An Appearance-tab save: no fb_* fields at all, plus the profile fields this
        // action always requires.
        $this->actingAs($admin)->post("/{$tenant->slug}/admin/settings", [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'site_title' => 'Renamed Agency',
            'primary_color' => '#ff8800',
        ])->assertRedirect();

        $facebook = Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('integration_type', 'facebook')->firstOrFail();

        $this->assertTrue((bool) $facebook->is_active, 'still enabled');
        $this->assertSame('1234567890', $facebook->config['page_id'], 'page id survived');
    }

    public function test_an_unrelated_save_does_not_invent_empty_integrations(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)->post("/{$tenant->slug}/admin/settings", [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name,
            'email' => $admin->email, 'site_title' => 'Renamed Agency',
        ])->assertRedirect();

        foreach (['facebook', 'twitter'] as $type) {
            $this->assertSame(
                0,
                Integration::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('integration_type', $type)->count(),
                "a save that never mentioned {$type} should not create it"
            );
        }
    }

    public function test_the_integrations_tab_can_still_disable_facebook(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $this->configuredFacebook($tenant);

        // Unticking Enable posts the hidden companion input with 0.
        $this->actingAs($admin)->post("/{$tenant->slug}/admin/settings", [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name,
            'email' => $admin->email,
            'fb_enabled' => '0', 'fb_page_id' => '1234567890',
        ])->assertRedirect();

        $facebook = Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('integration_type', 'facebook')->firstOrFail();

        $this->assertFalse((bool) $facebook->is_active, 'the toggle still works');
    }

    public function test_the_wizard_keeps_the_auto_post_choices_already_made(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $this->configuredFacebook($tenant);   // both auto-post flags deliberately off

        $this->actingAs($admin)->post("/{$tenant->slug}/admin/setup", [
            'step' => 4,
            'fb_enabled' => '1',
            'fb_page_id' => '1234567890',
        ]);

        $facebook = Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('integration_type', 'facebook')->firstOrFail();

        $this->assertFalse((bool) $facebook->config['post_on_new_listing'], 'wizard did not flip it back on');
        $this->assertFalse((bool) $facebook->config['post_on_sold']);
    }
}
