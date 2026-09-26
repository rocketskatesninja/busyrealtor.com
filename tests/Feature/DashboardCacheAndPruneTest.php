<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\PropertyView;
use App\Models\SiteSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The dashboard caches about twenty aggregates for five minutes, and four models flushed
 | that cache on write. Three of them are things an admin changes and expects to see at
 | once. The fourth was PropertyView, which gets a row on every public property pageview —
 | so a single visitor browsing listings invalidated the cache repeatedly and the TTL never
 | got to do anything.
 |
 | Those pageview rows were also never removed, while both aggregates that read them only
 | ever look back thirty days.
 */
class DashboardCacheAndPruneTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function key(int $tenantId): string
    {
        return 'dashboard:'.$tenantId.':v1';
    }

    private function recordView(int $tenantId, int $propertyId, $when = null): PropertyView
    {
        return PropertyView::withoutGlobalScopes()->create([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'ip_address' => '203.0.113.9',
            'viewed_at' => $when ?? now(),
        ]);
    }

    public function test_a_public_pageview_does_not_throw_away_the_dashboard_cache(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        Cache::put($this->key($tenant->id), ['sentinel' => true], 300);
        $this->recordView($tenant->id, $property->id);

        $this->assertTrue(
            Cache::has($this->key($tenant->id)),
            'a pageview flushed the dashboard cache, so the five-minute TTL never applies'
        );
    }

    public function test_a_change_an_admin_makes_still_refreshes_the_dashboard_at_once(): void
    {
        $tenant = $this->makeTenant();

        Cache::put($this->key($tenant->id), ['sentinel' => true], 300);

        Message::create([
            'tenant_id' => $tenant->id,
            'source' => 'contact_form',
            'sender_name' => 'Dana',
            'sender_email' => 'dana@example.test',
            'message' => 'Hello',
            'status' => 'new',
        ]);

        $this->assertFalse(Cache::has($this->key($tenant->id)), 'a new message left a stale dashboard');
    }

    public function test_a_listing_change_still_refreshes_the_dashboard_at_once(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        Cache::put($this->key($tenant->id), ['sentinel' => true], 300);
        $property->update(['listing_status' => 'sold']);

        $this->assertFalse(Cache::has($this->key($tenant->id)));
    }

    public function test_the_prune_drops_pageviews_past_ninety_days_and_keeps_the_rest(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $this->recordView($tenant->id, $property->id, now()->subDays(120));
        $this->recordView($tenant->id, $property->id, now()->subDays(91));
        $keptEdge = $this->recordView($tenant->id, $property->id, now()->subDays(89));
        $keptToday = $this->recordView($tenant->id, $property->id, now());

        $this->assertSame(4, PropertyView::withoutGlobalScopes()->count());

        $this->artisan('app:prune-page-views')->assertExitCode(0);

        $remaining = PropertyView::withoutGlobalScopes()->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$keptEdge->id, $keptToday->id], $remaining);
    }

    public function test_the_retention_window_is_configurable(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $old = $this->recordView($tenant->id, $property->id, now()->subDays(45));
        $recent = $this->recordView($tenant->id, $property->id, now()->subDays(10));

        $this->artisan('app:prune-page-views', ['--days' => 30])->assertExitCode(0);

        $this->assertSame([$recent->id], PropertyView::withoutGlobalScopes()->pluck('id')->all());
    }

    public function test_the_dashboard_still_counts_views_after_a_prune_window(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $property = $this->makeProperty($tenant);

        $this->recordView($tenant->id, $property->id, now()->subDays(5));
        $this->recordView($tenant->id, $property->id, now()->subDays(40));

        $this->actingAs($admin)->get("/{$tenant->slug}/admin")->assertOk();

        // The 30-day aggregate must see the recent one and not the older one.
        $this->assertSame(1, DB::table('property_views')
            ->where('tenant_id', $tenant->id)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->count());
    }

    public function test_two_settings_rows_for_one_tenant_are_refused(): void
    {
        $tenant = $this->makeTenant();

        $this->expectException(UniqueConstraintViolationException::class);

        SiteSettings::create([
            'tenant_id' => $tenant->id,
            'site_title' => 'A second settings row',
            'primary_color' => '#000000',
        ]);
    }

    public function test_two_appointments_cannot_share_a_confirmation_token(): void
    {
        $tenant = $this->makeTenant();

        $row = [
            'tenant_id' => $tenant->id,
            'visitor_name' => 'Dana',
            'visitor_email' => 'dana@example.test',
            'appointment_type' => 'showing',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'pending',
            'source' => 'website',
            'confirmation_token' => 'shared-token',
            'created_at' => now(),
        ];

        DB::table('appointments')->insert($row);

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('appointments')->insert($row);
    }

    /** Rows predating tokens have none, and a unique index must not treat those as clashes. */
    public function test_appointments_without_a_token_are_not_treated_as_duplicates(): void
    {
        $tenant = $this->makeTenant();

        $row = [
            'tenant_id' => $tenant->id,
            'visitor_name' => 'Dana',
            'visitor_email' => 'dana@example.test',
            'appointment_type' => 'showing',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'pending',
            'source' => 'website',
            'confirmation_token' => null,
            'created_at' => now(),
        ];

        DB::table('appointments')->insert($row);
        DB::table('appointments')->insert($row);

        $this->assertSame(2, DB::table('appointments')->whereNull('confirmation_token')->count());
    }
}
