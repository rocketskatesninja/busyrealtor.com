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

    /**
     * Every action the message list actually fires, exactly as the view fires it.
     *
     * The view's msgAction() always posts `{ action, id, status: value }` with status null for
     * star/read/delete. Adding an `in:` rule without `nullable` rejected that present-but-null
     * value — and since the fetch ignores the response and reloads, the buttons would have
     * silently stopped working. This is the test that catches over-restriction, which is the
     * real risk when adding an allow-list to a live path.
     */
    public function test_every_message_action_the_view_fires_still_works(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $make = fn () => Message::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'sender_name' => 'Cora', 'sender_email' => 'cora@example.test',
            'message' => 'Hello', 'status' => 'new', 'is_read' => false, 'is_starred' => false,
        ]);

        // star — posted with status: null, exactly as the view does
        $message = $make();
        $this->actingAs($admin)
            ->postJson("/{$tenant->slug}/admin/messages/action", ['action' => 'star', 'id' => $message->id, 'status' => null])
            ->assertSuccessful();
        $this->assertTrue((bool) $message->fresh()->is_starred);

        // read
        $message = $make();
        $this->actingAs($admin)
            ->postJson("/{$tenant->slug}/admin/messages/action", ['action' => 'read', 'id' => $message->id, 'status' => null])
            ->assertSuccessful();
        $this->assertTrue((bool) $message->fresh()->is_read);

        // status, the one arm that carries a value
        $message = $make();
        $this->actingAs($admin)
            ->postJson("/{$tenant->slug}/admin/messages/action", ['action' => 'status', 'id' => $message->id, 'status' => 'replied'])
            ->assertSuccessful();
        $this->assertSame('replied', $message->fresh()->status);

        // delete
        $message = $make();
        $this->actingAs($admin)
            ->postJson("/{$tenant->slug}/admin/messages/action", ['action' => 'delete', 'id' => $message->id, 'status' => null])
            ->assertSuccessful();
        $this->assertNull(Message::withoutGlobalScopes()->find($message->id));
    }

    /** The one action the appointments bulk form actually submits. */
    public function test_the_appointments_bulk_delete_the_view_submits_still_works(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $ids = collect(range(1, 3))->map(fn () => Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'visitor_name' => 'Vic', 'visitor_email' => 'vic@example.test',
            'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00:00',
            'appointment_type' => 'showing', 'status' => 'cancelled',
        ])->id)->all();

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/appointments/bulk", ['action' => 'delete', 'ids' => $ids])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
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
