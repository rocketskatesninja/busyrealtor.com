<?php

namespace Tests\Feature;

use App\Models\PropertyImage;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Property photos are stored at 1200px and were rendered straight into 390px cards, so a
 | listing grid downloaded several full-size photos to draw thumbnails. On the demo tenant
 | that was 1.9 MB of images against 21 KB of gzipped HTML.
 |
 | Uploads now write a card-sized copy beside the original, and the views that draw cards,
 | grids and strips ask for that one. The original is still what the carousel and the
 | lightbox load, because those render at full width.
 */
class ImageDerivativeTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** One admin per tenant: makeAdmin derives the email from the slug, so a second collides. */
    private array $admins = [];

    private function upload(int $tenantId, int $propertyId, int $w = 2400, int $h = 1600): PropertyImage
    {
        $tenant = \App\Models\Tenant::findOrFail($tenantId);
        $admin = $this->admins[$tenantId] ??= $this->makeAdmin($tenant);

        $json = $this->actingAs($admin)->post("/{$tenant->slug}/admin/api/property-images", [
            'property_id' => $propertyId,
            'image' => UploadedFile::fake()->image('photo.jpg', $w, $h),
        ])->assertOk()->json();

        return PropertyImage::withoutGlobalScopes()->findOrFail($json['id']);
    }

    public function test_an_upload_writes_a_card_sized_copy_beside_the_original(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id);
        $thumbnail = ImageStore::thumbnailFor($image->image_url);

        Storage::disk('public')->assertExists($image->image_url);
        Storage::disk('public')->assertExists($thumbnail);

        $this->assertSame(1200, Image::read(Storage::disk('public')->get($image->image_url))->width());
        $this->assertSame(600, Image::read(Storage::disk('public')->get($thumbnail))->width());
        $this->assertStringContainsString('/thumbs/', $thumbnail);
    }

    public function test_the_derivative_is_materially_smaller(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id);
        $disk = Storage::disk('public');

        $full = $disk->size($image->image_url);
        $thumb = $disk->size(ImageStore::thumbnailFor($image->image_url));

        $this->assertLessThan($full, $thumb, 'the derivative is not smaller than the original');
    }

    public function test_thumb_path_falls_back_to_the_original_when_none_was_generated(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        // A row from before derivatives existed: the file is there, the derivative is not.
        Storage::disk('public')->put('tenants/9/properties/legacy.jpg', 'not-really-a-jpeg');
        $image = PropertyImage::withoutGlobalScopes()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'image_url' => 'tenants/9/properties/legacy.jpg',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $this->assertSame('tenants/9/properties/legacy.jpg', $image->thumb_path);
    }

    public function test_thumb_path_is_used_once_a_derivative_exists(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id);

        $this->assertSame(ImageStore::thumbnailFor($image->image_url), $image->fresh()->thumb_path);
    }

    public function test_deleting_an_image_takes_its_derivative_with_it(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id);
        $thumbnail = ImageStore::thumbnailFor($image->image_url);
        $original = $image->image_url;

        $image->delete();

        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertMissing($thumbnail);
    }

    /**
     * A deliberate behaviour change, not a refactor: the upload path used scale(), which
     * *upscales* an image narrower than the target. A 900px photo became a 1200px JPEG —
     * more bytes, no more detail. scaleDown leaves it alone.
     */
    public function test_a_photo_narrower_than_the_target_is_not_upscaled(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id, 900, 900);

        $this->assertSame(900, Image::read(Storage::disk('public')->get($image->image_url))->width());
    }

    public function test_the_gallery_asks_for_derivatives_and_defers_what_is_below_the_fold(): void
    {
        $tenant = $this->makeTenant();

        foreach (range(1, 6) as $n) {
            $property = $this->makeProperty($tenant, ['title' => "Listing {$n}"]);
            $this->upload($tenant->id, $property->id);
        }

        $html = $this->get("/{$tenant->slug}/gallery")->assertOk()->content();

        $this->assertStringContainsString('/thumbs/', $html, 'the gallery still asks for full-size photos');
        $this->assertStringContainsString('loading="lazy"', $html, 'nothing below the fold is deferred');
    }

    public function test_the_backfill_generates_derivatives_for_existing_photos(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $image = $this->upload($tenant->id, $property->id);
        $thumbnail = ImageStore::thumbnailFor($image->image_url);

        // Take the derivative away, as it would be for any photo uploaded before this change.
        Storage::disk('public')->delete($thumbnail);
        Storage::disk('public')->assertMissing($thumbnail);

        $this->artisan('images:thumbnails')->assertExitCode(0);

        Storage::disk('public')->assertExists($thumbnail);
    }

    public function test_the_backfill_skips_rows_whose_file_is_gone(): void
    {
        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        PropertyImage::withoutGlobalScopes()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'image_url' => 'tenants/9/properties/vanished.jpg',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $this->artisan('images:thumbnails')->assertExitCode(0);
    }
}
