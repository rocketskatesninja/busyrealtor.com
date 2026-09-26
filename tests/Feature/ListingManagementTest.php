<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Characterization tests for managing listings and their photos — the core job of the admin
 | area, and completely untested until now.
 |
 | The upload assertions matter most: the same read→scale→encode→store block is written eight
 | times across the app and Phase 2 replaces all eight with one ImageStore. These record what
 | that block produces today (1200px wide, JPEG, first image primary, path under
 | tenants/{id}/properties) so the extraction can be proved to change nothing.
 */
class ListingManagementTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    private function validListing(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Marsh View Bungalow',
            'listing_status' => 'active',
            // Must be one of the DB enum values: house, condo, townhouse, land,
            // commercial, multi_family, other. The controller validates this only as
            // `required|string`, so anything else is a 500 rather than a validation error.
            'property_type' => 'house',
            'price' => 375000,
            'address' => '4 Tabby Lane',
            'bedrooms' => 3,
            'bathrooms' => 2,
        ];
    }

    public function test_the_listings_index_renders(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant, ['title' => 'Existing Listing']);

        $this->actingAs($this->makeAdmin($tenant))
            ->get("/{$tenant->slug}/admin/properties")
            ->assertOk()
            ->assertSee('Existing Listing', false);
    }

    public function test_an_admin_can_create_a_listing(): void
    {
        $tenant = $this->makeTenant();

        $this->actingAs($this->makeAdmin($tenant))
            ->post("/{$tenant->slug}/admin/properties", $this->validListing())
            ->assertRedirect("/{$tenant->slug}/admin/properties");

        $property = Property::withoutGlobalScopes()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('Marsh View Bungalow', $property->title);
        $this->assertSame('active', $property->listing_status);
        // The form field is `address`; the column is `address_street`.
        $this->assertSame('4 Tabby Lane', $property->address_street);
    }

    public function test_a_listing_needs_a_title_a_status_and_a_type(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/properties", [])
            ->assertSessionHasErrors(['title', 'listing_status', 'property_type']);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/properties", $this->validListing(['listing_status' => 'invented']))
            ->assertSessionHasErrors('listing_status');

        $this->assertSame(0, Property::withoutGlobalScopes()->count());
    }

    public function test_an_admin_can_update_and_delete_their_own_listing(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $property = $this->makeProperty($tenant, ['title' => 'Before']);

        $this->actingAs($admin)
            ->put("/{$tenant->slug}/admin/properties/{$property->id}", $this->validListing(['title' => 'After']))
            ->assertRedirect("/{$tenant->slug}/admin/properties");
        $this->assertSame('After', $property->fresh()->title);

        $this->actingAs($admin)
            ->delete("/{$tenant->slug}/admin/properties/{$property->id}")
            ->assertRedirect("/{$tenant->slug}/admin/properties");
        $this->assertNull(Property::withoutGlobalScopes()->find($property->id));
    }

    public function test_an_admin_cannot_touch_another_tenants_listing(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $admin = $this->makeAdmin($mine);
        $foreign = $this->makeProperty($theirs, ['title' => 'Not Yours']);

        $this->actingAs($admin)->get("/{$mine->slug}/admin/properties/{$foreign->id}/edit")->assertNotFound();
        $this->actingAs($admin)->put("/{$mine->slug}/admin/properties/{$foreign->id}", $this->validListing())->assertNotFound();
        $this->actingAs($admin)->delete("/{$mine->slug}/admin/properties/{$foreign->id}")->assertNotFound();

        $this->assertSame('Not Yours', $foreign->fresh()->title);
    }

    public function test_an_admin_of_one_tenant_cannot_enter_another_tenants_admin_area(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);

        $this->actingAs($this->makeAdmin($mine))
            ->get("/{$theirs->slug}/admin/properties")
            ->assertForbidden();
    }

    /** Records exactly what the duplicated upload block produces. */
    public function test_an_uploaded_photo_is_scaled_to_1200_and_the_first_one_is_primary(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $property = $this->makeProperty($tenant);

        $first = $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images", [
            'property_id' => $property->id,
            'image' => UploadedFile::fake()->image('big.jpg', 2400, 1600),
        ])->assertOk()->json();

        $this->assertTrue($first['is_primary']);

        $image = PropertyImage::withoutGlobalScopes()->findOrFail($first['id']);
        $this->assertStringStartsWith("tenants/{$tenant->id}/properties/", $image->image_url);
        Storage::disk('public')->assertExists($image->image_url);

        // Scaled down on the way in, and re-encoded as JPEG whatever arrived.
        $stored = Image::read(Storage::disk('public')->get($image->image_url));
        $this->assertSame(1200, $stored->width());
        $this->assertSame(800, $stored->height(), 'aspect ratio preserved');

        $second = $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images", [
            'property_id' => $property->id,
            'image' => UploadedFile::fake()->image('second.jpg', 900, 900),
        ])->assertOk()->json();

        $this->assertFalse($second['is_primary'], 'only the first upload is primary');
    }

    public function test_primary_can_be_moved_and_an_image_can_be_deleted(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $property = $this->makeProperty($tenant);

        $one = $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images", [
            'property_id' => $property->id, 'image' => UploadedFile::fake()->image('a.jpg', 800, 600),
        ])->json();
        $two = $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images", [
            'property_id' => $property->id, 'image' => UploadedFile::fake()->image('b.jpg', 800, 600),
        ])->json();

        $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images/{$two['id']}/primary")->assertOk();

        $this->assertFalse(PropertyImage::withoutGlobalScopes()->find($one['id'])->is_primary);
        $this->assertTrue(PropertyImage::withoutGlobalScopes()->find($two['id'])->is_primary);

        $this->actingAs($admin)->delete("/{$tenant->slug}/admin/api/property-images/{$one['id']}")->assertOk();
        $this->assertNull(PropertyImage::withoutGlobalScopes()->find($one['id']));
    }

    public function test_a_photo_cannot_be_attached_to_another_tenants_listing(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $foreign = $this->makeProperty($theirs);

        $this->actingAs($this->makeAdmin($mine))->post("/{$mine->slug}/admin/api/property-images", [
            'property_id' => $foreign->id,
            'image' => UploadedFile::fake()->image('x.jpg', 800, 600),
        ])->assertNotFound();

        $this->assertSame(0, PropertyImage::withoutGlobalScopes()->count());
    }

    /** Staff management is gated on the Pro plan; a trial tenant is redirected to billing. */
    public function test_a_trial_tenant_is_redirected_away_from_pro_only_features(): void
    {
        $tenant = $this->makeTenant(['slug' => 'trialco', 'plan' => 'trial']);

        $this->actingAs($this->makeAdmin($tenant))
            ->get("/{$tenant->slug}/admin/staff")
            ->assertRedirect("/{$tenant->slug}/admin/billing")
            ->assertSessionHas('error');
    }

    public function test_a_pro_tenant_reaches_staff_management(): void
    {
        $tenant = $this->makeTenant(['slug' => 'proco', 'plan' => 'pro']);

        $this->actingAs($this->makeAdmin($tenant))
            ->get("/{$tenant->slug}/admin/staff")
            ->assertOk();
    }
}
