<?php

namespace Tests\Support;

use App\Models\Property;
use App\Models\SiteSettings;
use App\Models\StaffMember;
use App\Models\Tenant;
use App\Models\User;

/**
 * Builders for the fixtures every feature test needs.
 *
 * There are no factories for these models, so each existing test hand-rolls a tenant, its
 * settings row and an admin — five near-identical blocks and counting. This is that block,
 * once. It deliberately produces a *complete* tenant (settings row included), because a
 * tenant without one behaves differently in several places and a test that forgets it fails
 * for the wrong reason.
 */
trait MakesTenants
{
    protected function makeTenant(array $attributes = [], array $settings = []): Tenant
    {
        $slug = $attributes['slug'] ?? 'acme'.substr(md5(uniqid('', true)), 0, 6);

        $tenant = Tenant::create($attributes + [
            'slug' => $slug,
            'name' => 'Acme Realty',
            'email' => $slug.'@example.test',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        SiteSettings::create($settings + [
            'tenant_id' => $tenant->id,
            'site_title' => 'Acme Realty',
            'primary_color' => '#123456',
            'header_display_mode' => 'favicon_text',
            'hero_background_type' => 'gradient',
            'setup_completed' => true,
            'hero_effects' => ['entrance_animation' => true, 'particles' => false, 'overlay_opacity' => 30],
            'homepage_sections' => [['id' => 'hero', 'order' => 0], ['id' => 'listings', 'order' => 1]],
        ]);

        return $tenant->fresh();
    }

    protected function makeAdmin(Tenant $tenant, array $attributes = []): User
    {
        return User::create($attributes + [
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'email' => 'admin-'.$tenant->slug.'@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
    }

    protected function makeSuperAdmin(array $attributes = []): User
    {
        return User::create($attributes + [
            'first_name' => 'Sue',
            'last_name' => 'Super',
            'email' => 'super-'.substr(md5(uniqid('', true)), 0, 6).'@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    protected function makeProperty(Tenant $tenant, array $attributes = []): Property
    {
        return Property::withoutGlobalScopes()->create($attributes + [
            'tenant_id' => $tenant->id,
            'title' => 'Seaside Cottage',
            'listing_status' => 'active',
            'price' => 450000,
            'address_street' => '1 Test Lane',
            'address_city' => 'Brunswick',
            'address_state' => 'GA',
            'address_zip' => '31520',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'square_feet' => 1800,
            'latitude' => 31.1499,
            'longitude' => -81.4915,
        ]);
    }

    protected function makeStaff(Tenant $tenant, array $attributes = []): StaffMember
    {
        return StaffMember::withoutGlobalScopes()->create($attributes + [
            'tenant_id' => $tenant->id,
            'name' => 'Sam Agent',
        ]);
    }
}
