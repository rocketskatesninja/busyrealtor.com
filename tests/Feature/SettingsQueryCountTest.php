<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The same settings lookup — SiteSettings::where('tenant_id', …)->first() — was written out in
 | twelve places across controllers, a provider, a command, a service and two views, while
 | Tenant::settings() already existed and returns the same row through a relation.
 |
 | The relation is cached on the tenant instance, and app('tenant') is one shared instance for
 | the request, so the repeated lookups in a single render collapse into one query. This test
 | measures that rather than asserting it.
 */
class SettingsQueryCountTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function countSettingsQueries(callable $request): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'site_settings')) {
                $count++;
            }
        });

        $request();

        return $count;
    }

    public function test_a_public_page_looks_settings_up_once(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        $queries = $this->countSettingsQueries(fn () => $this->get("/{$tenant->slug}")->assertOk());

        $this->assertLessThanOrEqual(1, $queries, "the public home page ran {$queries} settings queries");
    }

    public function test_the_admin_dashboard_looks_settings_up_once(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $queries = $this->countSettingsQueries(
            fn () => $this->actingAs($admin)->get("/{$tenant->slug}/admin")->assertOk()
        );

        $this->assertLessThanOrEqual(1, $queries, "the dashboard ran {$queries} settings queries");
    }

    public function test_a_property_page_looks_settings_up_once(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $queries = $this->countSettingsQueries(
            fn () => $this->get("/{$tenant->slug}/property/{$property->id}")->assertOk()
        );

        $this->assertLessThanOrEqual(1, $queries, "the property page ran {$queries} settings queries");
    }
}
