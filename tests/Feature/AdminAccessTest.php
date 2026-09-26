<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Characterization tests for who may reach what: the super-admin console, impersonation,
 | billing, export, and the public booking endpoint.
 |
 | Authorization here is middleware-only — there are no policies and not a single
 | authorize()/Gate:: call in the app — so these routes and their middleware stacks *are* the
 | access-control model. That makes them worth pinning down before anything is rearranged.
 */
class AdminAccessTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    public function test_guests_are_sent_to_login_from_the_admin_area(): void
    {
        $tenant = $this->makeTenant();

        $this->get("/{$tenant->slug}/admin")->assertRedirect('/login');
        $this->get('/super-admin')->assertRedirect('/login');
    }

    /** Note the inconsistency, recorded not judged: tenant-admin denial is a bare 403,
     *  while super-admin denial is a friendly redirect. Plan item C13. */
    public function test_a_tenant_admin_cannot_reach_the_super_admin_console(): void
    {
        $tenant = $this->makeTenant();

        $this->actingAs($this->makeAdmin($tenant))
            ->get('/super-admin')
            ->assertRedirect('/login');
    }

    public function test_the_super_admin_console_pages_render(): void
    {
        $super = $this->makeSuperAdmin();
        $this->makeTenant(['slug' => 'listed']);

        foreach (['/super-admin', '/super-admin/tenants', '/super-admin/activity',
            '/super-admin/feedback', '/super-admin/settings', '/super-admin/mailer'] as $path) {
            $this->actingAs($super)->get($path)->assertOk();
        }
    }

    public function test_a_super_admin_can_impersonate_a_tenant_and_stop_again(): void
    {
        $super = $this->makeSuperAdmin();
        $tenant = $this->makeTenant(['slug' => 'impersonated']);
        $this->makeAdmin($tenant);

        $this->actingAs($super)
            // Tenant::getRouteKeyName() is "slug", so this route takes the slug, not the id.
            ->post("/super-admin/impersonate/{$tenant->slug}")
            ->assertRedirect("/{$tenant->slug}/admin");

        $this->assertSame($tenant->id, session('impersonating_tenant_id'));
        $this->assertSame($super->id, session('super_admin_id'));

        // While impersonating, the super admin reaches the tenant's admin area even though
        // their own user row has no tenant_id.
        $this->actingAs($super)->get("/{$tenant->slug}/admin")->assertOk();

        $this->actingAs($super)->post('/super-admin/stop-impersonate')->assertRedirect('/super-admin');
        $this->assertNull(session('impersonating_tenant_id'));
    }

    public function test_a_tenant_admin_cannot_impersonate_anyone(): void
    {
        $tenant = $this->makeTenant();
        $other = $this->makeTenant(['slug' => 'target']);

        $this->actingAs($this->makeAdmin($tenant))
            ->post("/super-admin/impersonate/{$other->slug}")
            ->assertRedirect('/login');

        $this->assertNull(session('impersonating_tenant_id'));
    }

    /** Billing deliberately skips tenant.active/verified so an unpaid tenant can still pay. */
    public function test_billing_is_reachable_even_for_an_inactive_unverified_tenant(): void
    {
        $tenant = $this->makeTenant(['slug' => 'lapsed', 'is_active' => false]);
        $admin = $this->makeAdmin($tenant, ['email_verified_at' => null]);

        $this->actingAs($admin)->get("/{$tenant->slug}/admin/billing")->assertOk();
    }

    public function test_export_returns_this_tenants_own_data_as_csv(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $this->makeProperty($mine, ['title' => 'My Listing']);
        $this->makeProperty($theirs, ['title' => 'Their Listing']);

        $response = $this->actingAs($this->makeAdmin($mine))
            ->get("/{$mine->slug}/admin/api/export/properties");

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('My Listing', $body);
        $this->assertStringNotContainsString('Their Listing', $body);
    }

    public function test_a_visitor_can_book_an_appointment_on_a_public_site(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $this->post("/{$tenant->slug}/appointments", [
            'visitor_name' => 'Vic Visitor',
            'visitor_email' => 'vic@example.test',
            'visitor_phone' => '9125550000',
            'property_id' => $property->id,
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '14:00',
            'message' => 'Keen to see this one.',
        ])->assertOk()->assertJson(['success' => true]);   // JSON, not a redirect

        $appointment = Appointment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($tenant->id, $appointment->tenant_id);
        $this->assertSame('Vic Visitor', $appointment->visitor_name);
    }

    public function test_a_visitor_can_send_a_contact_message(): void
    {
        $tenant = $this->makeTenant();

        $this->post("/{$tenant->slug}/api/contact", [
            'name' => 'Cora Contact',
            'email' => 'cora@example.test',
            'message' => 'Please call me about listings.',
        ])->assertSuccessful();

        $message = \App\Models\Message::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($tenant->id, $message->tenant_id);
    }
}
