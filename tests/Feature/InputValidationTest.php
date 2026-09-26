<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Several writes reached the database with no allow-list, and the columns behind them are
 | enums. MySQL runs in strict mode here, so an unexpected value was not a validation error
 | for the user — it was a 500, or a swallowed QueryException, depending on the path.
 |
 | These assert the corrected behaviour: rejected at the door, with the stored row untouched.
 */
class InputValidationTest extends TestCase
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

    public function test_a_property_type_outside_the_enum_is_a_validation_error_not_a_500(): void
    {
        $tenant = $this->makeTenant();

        $this->actingAs($this->makeAdmin($tenant))
            ->post("/{$tenant->slug}/admin/properties", [
                'title' => 'Nice House', 'listing_status' => 'active', 'property_type' => 'castle',
            ])
            ->assertSessionHasErrors('property_type');

        $this->assertSame(0, Property::withoutGlobalScopes()->count());
    }

    public function test_every_enum_value_the_form_offers_is_still_accepted(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        foreach (['house', 'condo', 'townhouse', 'land', 'commercial', 'multi_family', 'other'] as $type) {
            $this->actingAs($admin)
                ->post("/{$tenant->slug}/admin/properties", [
                    'title' => "A {$type}", 'listing_status' => 'active', 'property_type' => $type,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(7, Property::withoutGlobalScopes()->count());
    }

    public function test_an_invented_appointment_status_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'visitor_name' => 'Vic', 'visitor_email' => 'vic@example.test',
            'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00:00',
            'appointment_type' => 'showing', 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/appointments/{$appointment->id}/action", ['status' => 'banana'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $appointment->fresh()->status, 'the row is untouched');

        // And the real values still work.
        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/appointments/{$appointment->id}/action", ['status' => 'confirmed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_an_invented_message_status_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $message = Message::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'sender_name' => 'Cora', 'sender_email' => 'cora@example.test',
            'message' => 'Hello', 'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/messages/action", [
                'id' => $message->id, 'action' => 'status', 'status' => 'banana',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('new', $message->fresh()->status);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/messages/action", [
                'id' => $message->id, 'action' => 'status', 'status' => 'replied',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('replied', $message->fresh()->status);
    }

    public function test_a_settings_upload_that_is_not_an_image_is_refused_rather_than_throwing(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)->post("/{$tenant->slug}/admin/settings", [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name, 'email' => $admin->email,
            'hero_image' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
        ])->assertSessionHasErrors('hero_image');
    }

    public function test_a_real_image_still_uploads_through_settings(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)->post("/{$tenant->slug}/admin/settings", [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name, 'email' => $admin->email,
            'owner_photo' => UploadedFile::fake()->image('me.jpg', 900, 900),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists("tenants/{$tenant->id}/owner.jpg");
    }
}
