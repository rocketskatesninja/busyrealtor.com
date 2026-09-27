<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Services\AppointmentBooker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Booking an appointment was written four times — the public form, the public chatbot, the
 | admin form and the assistant's tool — and the copies had drifted in ways only a row
 | comparison would reveal: 09:00 versus 10:00 defaults, a source column left NULL on every
 | website booking, and two flood limits that counted different things.
 |
 | The sharper one: the admin form's dropdown offers seven appointment types and the column
 | accepted four, so inspection, open_house, listing_appointment and closing were strict-mode
 | truncation errors — four of seven choices returned a 500 instead of booking anything.
 */
class AppointmentBookerTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function bookingPayload(array $overrides = []): array
    {
        return $overrides + [
            'visitor_name' => 'Dana Buyer',
            'visitor_email' => 'dana@example.test',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_type' => 'showing',
        ];
    }

    /** Every type the admin dropdown offers has to be storable. */
    public function test_every_type_the_form_offers_can_be_booked(): void
    {
        $tenant = $this->makeTenant();

        $offered = ['showing', 'consultation', 'inspection', 'open_house', 'listing_appointment', 'closing', 'other'];

        foreach ($offered as $type) {
            $appointment = AppointmentBooker::book($tenant, $this->bookingPayload([
                'appointment_type' => $type,
                'source' => 'admin',
            ]));

            $this->assertSame($type, $appointment->fresh()->appointment_type, "{$type} did not survive the insert");
        }
    }

    public function test_the_admin_form_rejects_a_type_the_column_cannot_hold(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/appointments", [
                'visitor_name' => 'Dana',
                'visitor_email' => 'dana@example.test',
                'appointment_date' => now()->addDay()->toDateString(),
                'appointment_type' => 'moon_landing',
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('appointment_type');

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    /** The public form stored nothing here, so every website booking was a NULL source. */
    public function test_a_website_booking_records_where_it_came_from(): void
    {
        $tenant = $this->makeTenant();

        $this->postJson("/{$tenant->slug}/appointments", [
            'visitor_name' => 'Dana Buyer',
            'visitor_email' => 'dana@example.test',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_type' => 'showing',
            'appointment_time' => '14:00',
        ])->assertOk();

        $this->assertSame('website', Appointment::withoutGlobalScopes()->firstOrFail()->source);
    }

    /** 09:00 on the form, 10:00 in the chatbot, for no stated reason. Now one default. */
    public function test_a_booking_with_no_time_gets_one_consistent_default(): void
    {
        $tenant = $this->makeTenant();

        foreach (['website', 'chatbot', 'admin'] as $source) {
            $appointment = AppointmentBooker::book($tenant, $this->bookingPayload([
                'visitor_email' => "no-time-{$source}@example.test",
                'appointment_time' => null,
                'source' => $source,
            ]));

            $this->assertSame('09:00:00', $appointment->fresh()->appointment_time, "{$source} used a different default");
        }
    }

    /**
     * The form allowed three per email and the chatbot two, but the chatbot only counted
     * chatbot rows — so five requests fitted through the pair of them.
     */
    public function test_the_daily_limit_counts_every_public_channel_together(): void
    {
        $tenant = $this->makeTenant();
        $email = 'persistent@example.test';

        AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => $email, 'source' => 'chatbot']));
        AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => $email, 'source' => 'chatbot']));
        $this->assertFalse(AppointmentBooker::floodLimitReached($tenant, $email), 'two should still be under the limit');

        AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => $email, 'source' => 'website']));
        $this->assertTrue(AppointmentBooker::floodLimitReached($tenant, $email),
            'the third across both channels should have reached the limit');
    }

    /** An agent booking a client in should not use up that client's own allowance. */
    public function test_appointments_an_admin_entered_do_not_count_against_the_visitor(): void
    {
        $tenant = $this->makeTenant();
        $email = 'client@example.test';

        foreach (range(1, 5) as $i) {
            AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => $email, 'source' => 'admin']));
        }

        $this->assertFalse(AppointmentBooker::floodLimitReached($tenant, $email));
    }

    public function test_the_limit_is_per_tenant_and_per_email(): void
    {
        $tenant = $this->makeTenant();
        $other = $this->makeTenant();

        foreach (range(1, 3) as $i) {
            AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => 'a@example.test', 'source' => 'website']));
        }

        $this->assertTrue(AppointmentBooker::floodLimitReached($tenant, 'a@example.test'));
        $this->assertFalse(AppointmentBooker::floodLimitReached($tenant, 'b@example.test'), 'a different visitor');
        $this->assertFalse(AppointmentBooker::floodLimitReached($other, 'a@example.test'), 'a different agency');
    }

    /** Yesterday's requests should not hold a visitor out today. */
    public function test_the_limit_only_looks_at_the_last_day(): void
    {
        $tenant = $this->makeTenant();
        $email = 'yesterday@example.test';

        foreach (range(1, 3) as $i) {
            $appointment = AppointmentBooker::book($tenant, $this->bookingPayload(['visitor_email' => $email, 'source' => 'website']));
            DB::table('appointments')->where('id', $appointment->id)->update(['created_at' => now()->subDays(2)]);
        }

        $this->assertFalse(AppointmentBooker::floodLimitReached($tenant, $email));
    }

    /** A property belonging to someone else must not end up on this tenant's appointment. */
    public function test_another_tenants_property_is_not_attached(): void
    {
        $tenant = $this->makeTenant();
        $other = $this->makeTenant();
        $theirProperty = $this->makeProperty($other);

        $appointment = AppointmentBooker::book($tenant, $this->bookingPayload([
            'property_id' => $theirProperty->id,
            'source' => 'website',
        ]));

        $this->assertNull($appointment->property_id);
    }

    public function test_the_staff_member_comes_from_the_property(): void
    {
        $tenant = $this->makeTenant();
        $staff = $this->makeStaff($tenant, ['email' => 'sam@example.test']);
        $property = $this->makeProperty($tenant, ['staff_member_id' => $staff->id]);

        $appointment = AppointmentBooker::book($tenant, $this->bookingPayload([
            'property_id' => $property->id,
            'source' => 'website',
        ]));

        $this->assertSame($staff->id, $appointment->staff_member_id);
    }
}
