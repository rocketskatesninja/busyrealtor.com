<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Foreign keys pointing at another tenant's rows.
 |
 | `exists:properties,id` queries the table directly, so it never saw the BelongsToTenant
 | global scope: a tenant-A admin could post tenant B's property or staff id and have it
 | stored on A's appointment. Nothing leaked — every read re-scopes — but the row pointed
 | somewhere it should not, and the staff notification then silently never fired. There are no
 | FK constraints on those columns to catch it either.
 |
 | The public booking form and the contact form had the same shape from the other direction:
 | they looked the property up through the scoped model and then stored the raw posted id
 | regardless of what the lookup found.
 */
class CrossTenantForeignKeyTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    public function test_an_admin_cannot_attach_another_tenants_property_or_staff(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);

        $foreignProperty = $this->makeProperty($theirs);
        $foreignStaff = $this->makeStaff($theirs, ['name' => 'Their Agent']);

        $this->actingAs($this->makeAdmin($mine))
            ->post("/{$mine->slug}/admin/appointments", [
                'visitor_name'     => 'Vic Visitor',
                'visitor_email'    => 'vic@example.test',
                'appointment_date' => now()->addDay()->toDateString(),
                'appointment_type' => 'showing',
                'status'           => 'pending',
                'property_id'      => $foreignProperty->id,
                'staff_member_id'  => $foreignStaff->id,
            ])
            ->assertSessionHasErrors(['property_id', 'staff_member_id']);

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_an_admin_can_still_attach_their_own_property_and_staff(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $property = $this->makeProperty($mine);
        $staff = $this->makeStaff($mine, ['name' => 'My Agent']);

        $this->actingAs($this->makeAdmin($mine))
            ->post("/{$mine->slug}/admin/appointments", [
                'visitor_name'     => 'Vic Visitor',
                'visitor_email'    => 'vic@example.test',
                'appointment_date' => now()->addDay()->toDateString(),
                'appointment_type' => 'showing',
                'status'           => 'pending',
                'property_id'      => $property->id,
                'staff_member_id'  => $staff->id,
            ])
            ->assertSessionHasNoErrors();

        $appointment = Appointment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($property->id, $appointment->property_id);
        $this->assertSame($staff->id, $appointment->staff_member_id);
    }

    public function test_a_public_booking_drops_a_property_id_from_another_tenant(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine', 'plan' => 'pro']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $foreign = $this->makeProperty($theirs);

        $this->post("/{$mine->slug}/appointments", [
            'visitor_name'     => 'Vic Visitor',
            'visitor_email'    => 'vic@example.test',
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '14:00',
            'property_id'      => $foreign->id,
        ])->assertOk();

        $appointment = Appointment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($mine->id, $appointment->tenant_id);
        $this->assertNull($appointment->property_id, 'the foreign id is dropped, not stored');
    }

    public function test_a_public_booking_keeps_a_property_id_of_its_own(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine', 'plan' => 'pro']);
        $property = $this->makeProperty($mine);

        $this->post("/{$mine->slug}/appointments", [
            'visitor_name'     => 'Vic Visitor',
            'visitor_email'    => 'vic@example.test',
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '14:00',
            'property_id'      => $property->id,
        ])->assertOk();

        $this->assertSame($property->id, Appointment::withoutGlobalScopes()->firstOrFail()->property_id);
    }

    public function test_a_contact_message_drops_a_property_id_from_another_tenant(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $theirs = $this->makeTenant(['slug' => 'theirs']);
        $foreign = $this->makeProperty($theirs);

        $this->post("/{$mine->slug}/api/contact", [
            'name' => 'Cora', 'email' => 'cora@example.test', 'message' => 'Hello',
            'property_id' => $foreign->id,
        ])->assertSuccessful();

        $message = Message::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($mine->id, $message->tenant_id);
        $this->assertNull($message->property_id);
    }

    public function test_a_contact_message_keeps_a_property_id_of_its_own(): void
    {
        $mine = $this->makeTenant(['slug' => 'mine']);
        $property = $this->makeProperty($mine);

        $this->post("/{$mine->slug}/api/contact", [
            'name' => 'Cora', 'email' => 'cora@example.test', 'message' => 'Hello',
            'property_id' => $property->id,
        ])->assertSuccessful();

        $this->assertSame($property->id, Message::withoutGlobalScopes()->firstOrFail()->property_id);
    }
}
