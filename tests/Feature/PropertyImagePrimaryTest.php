<?php

namespace Tests\Feature;

use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * The photo grid calls the primary endpoint on every reorder, upload and delete, and it
 * passes whichever photo currently sits first — which is usually the one that is already
 * primary. That case has to be a no-op, not a way to lose the flag.
 */
class PropertyImagePrimaryTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function images(): array
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $images = collect(range(0, 2))->map(fn ($i) => PropertyImage::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'image_url' => "tenants/{$tenant->id}/properties/photo-{$i}.jpg",
            'is_primary' => $i === 0,
            'sort_order' => $i,
        ]))->all();

        $this->actingAs($this->makeAdmin($tenant));

        return [$tenant, $images];
    }

    public function test_promoting_the_image_that_is_already_primary_keeps_it_primary(): void
    {
        [$tenant, $images] = $this->images();

        $this->post("/{$tenant->slug}/admin/api/property-images/{$images[0]->id}/primary")
            ->assertOk();

        $this->assertTrue($images[0]->fresh()->is_primary);
        $this->assertFalse($images[1]->fresh()->is_primary);
        $this->assertFalse($images[2]->fresh()->is_primary);
    }

    public function test_promoting_a_different_image_moves_the_flag(): void
    {
        [$tenant, $images] = $this->images();

        $this->post("/{$tenant->slug}/admin/api/property-images/{$images[2]->id}/primary")
            ->assertOk();

        $this->assertFalse($images[0]->fresh()->is_primary);
        $this->assertTrue($images[2]->fresh()->is_primary);
    }

    public function test_a_property_never_ends_up_with_two_primary_images(): void
    {
        [$tenant, $images] = $this->images();

        foreach ([$images[1], $images[1], $images[0]] as $target) {
            $this->post("/{$tenant->slug}/admin/api/property-images/{$target->id}/primary")
                ->assertOk();
        }

        $this->assertSame(1, PropertyImage::where('property_id', $images[0]->property_id)
            ->where('is_primary', true)->count());
    }

    public function test_another_tenants_image_cannot_be_promoted(): void
    {
        [$tenant, $images] = $this->images();

        $other = $this->makeTenant(['slug' => 'other-agency']);
        $this->actingAs($this->makeAdmin($other));

        // 404 rather than 403: BelongsToTenant's global scope hides the row before
        // ownedImage()'s own tenant check can run, so that abort(403) is a backstop for
        // a query that escapes the scope rather than the path taken here.
        $this->post("/{$other->slug}/admin/api/property-images/{$images[0]->id}/primary")
            ->assertNotFound();

        $this->assertTrue($images[0]->fresh()->is_primary);
    }
}
