<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Two gaps where a URL and the thing behind it disagreed.
 |
 | The platform lock hid every public page behind a 503 while the four public endpoints sat
 | outside the group: a locked platform still took contact submissions and bookings, served
 | the listings JSON, and ran chatbot turns against the tenant's own AI key.
 |
 | Impersonation bound the tenant from the session instead of the slug, so an admin page
 | addressed to one agency was filled with another's data — and a form on it wrote to the
 | agency in the session, not the one named in the URL and in every heading on the page.
 */
class PlatformLockAndImpersonationTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function lockPlatform(): void
    {
        SystemSetting::current()->update([
            'site_locked' => true,
            'lock_message' => 'Back shortly.',
        ]);
    }

    public function test_a_locked_platform_stops_taking_contact_submissions(): void
    {
        $tenant = $this->makeTenant();
        $this->lockPlatform();

        $this->postJson("/{$tenant->slug}/api/contact", [
            'name' => 'Dana',
            'email' => 'dana@example.test',
            'message' => 'Is it available?',
        ])->assertStatus(503);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_locked_platform_stops_serving_the_listings_api(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);
        $this->lockPlatform();

        $this->get("/{$tenant->slug}/api/properties")->assertStatus(503);
    }

    public function test_a_locked_platform_stops_running_chatbot_turns(): void
    {
        $tenant = $this->makeTenant();
        $this->lockPlatform();

        $this->postJson("/{$tenant->slug}/api/chatbot", [
            'message' => 'Hello',
            'session_id' => 'visitor_1',
        ])->assertStatus(503);
    }

    public function test_a_locked_platform_stops_taking_public_bookings(): void
    {
        $tenant = $this->makeTenant();
        $this->lockPlatform();

        $this->postJson("/{$tenant->slug}/appointments", [
            'visitor_name' => 'Dana',
            'visitor_email' => 'dana@example.test',
            'appointment_type' => 'showing',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '14:00',
        ])->assertStatus(503);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_a_super_admin_can_still_reach_a_locked_site(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);
        $this->lockPlatform();

        $this->actingAs($this->makeSuperAdmin())
            ->get("/{$tenant->slug}/api/properties")
            ->assertOk();
    }

    public function test_the_lock_leaves_the_public_site_alone_when_it_is_off(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        $this->get("/{$tenant->slug}/api/properties")->assertOk();
    }

    public function test_impersonating_one_account_does_not_open_another_accounts_admin(): void
    {
        $a = $this->makeTenant(['slug' => 'agency-a']);
        $b = $this->makeTenant(['slug' => 'agency-b']);
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->withSession(['super_admin_id' => $super->id, 'impersonating_tenant_id' => $a->id])
            ->get("/{$b->slug}/admin")
            ->assertForbidden();
    }

    public function test_impersonating_an_account_still_opens_that_accounts_admin(): void
    {
        $a = $this->makeTenant(['slug' => 'agency-a']);
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->withSession(['super_admin_id' => $super->id, 'impersonating_tenant_id' => $a->id])
            ->get("/{$a->slug}/admin")
            ->assertOk();
    }

    public function test_an_impersonated_page_is_never_filled_with_another_accounts_data(): void
    {
        $a = $this->makeTenant(['slug' => 'agency-a'], ['site_title' => 'Agency A Realty']);
        $b = $this->makeTenant(['slug' => 'agency-b'], ['site_title' => 'Agency B Realty']);
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)
            ->withSession(['super_admin_id' => $super->id, 'impersonating_tenant_id' => $a->id])
            ->get("/{$b->slug}");

        // Previously this returned 200 and rendered A's site under B's URL.
        $response->assertForbidden();
        $response->assertDontSee('Agency A Realty', false);
    }

    /**
     * A guard rather than a reported hole: no current flow leaves super_admin_id in a session
     * without impersonating_tenant_id, because they are set together and both cleared on login
     * and on stop. But the bypass keyed on super_admin_id alone, so such a session was a
     * tenant-admin session for every account on the platform. Now the bypass names one tenant.
     */
    public function test_an_impersonation_marker_without_a_tenant_is_not_a_master_key(): void
    {
        $a = $this->makeTenant(['slug' => 'agency-a']);
        $b = $this->makeTenant(['slug' => 'agency-b']);
        $adminOfA = $this->makeAdmin($a);

        $this->actingAs($adminOfA)
            ->withSession(['super_admin_id' => 999])
            ->get("/{$b->slug}/admin")
            ->assertForbidden();
    }
}
