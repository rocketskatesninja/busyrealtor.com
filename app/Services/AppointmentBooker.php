<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\Tenant;

/**
 * Creating an appointment, in one place.
 *
 * It was written four times — the public booking form, the public chatbot, the admin form and
 * the admin assistant's tool — and the copies had drifted in ways nobody would notice until
 * they compared two rows:
 *
 *   default time      09:00 on the form, 10:00 in the chatbot
 *   source            'chatbot' and 'admin' were set; the public form set nothing at all, so
 *                     every website booking stored NULL even though the enum has 'website'
 *   flood limit       3 per email per day on the form counting every source, 2 per email per
 *                     day in the chatbot counting only chatbot rows — so the same visitor
 *                     could file five requests by using both
 *   valid types       the form validated 'required|string' against a four-value enum
 *
 * What is deliberately *not* here: notifications and calendar sync. Those legitimately differ
 * — the admin form drives them from checkboxes, the public paths always notify the owner, and
 * the assistant notifies nobody because the admin is sitting right there. Calendar sync is not
 * an inconsistency either: only confirmed appointments reach a calendar, and public bookings
 * arrive pending, so they sync when the admin confirms them.
 */
class AppointmentBooker
{
    /**
     * Every value appointments.appointment_type accepts.
     *
     * Kept here so validation and the column cannot drift apart again: four of the seven
     * options in the admin dropdown used to be missing from the enum, which made them a 500.
     */
    public const TYPES = [
        'showing',
        'consultation',
        'follow_up',
        'inspection',
        'open_house',
        'listing_appointment',
        'closing',
        'other',
    ];

    /** Bookings a visitor may file per email per day, across every public channel. */
    public const DAILY_LIMIT = 3;

    private const DEFAULT_TIME = '09:00:00';

    /**
     * Has this visitor already filed the day's worth of requests?
     *
     * Counts every visitor-initiated source together. Counting only one channel is what let
     * someone file the chatbot's allowance and the form's allowance separately. Appointments
     * an admin entered do not count: a busy agent booking a client in should not lock that
     * client out of the website.
     */
    public static function floodLimitReached(Tenant $tenant, ?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return Appointment::where('tenant_id', $tenant->id)
            ->where('visitor_email', $email)
            ->whereIn('source', ['website', 'chatbot'])
            ->where('created_at', '>=', now()->subDay())
            ->count() >= self::DAILY_LIMIT;
    }

    /**
     * Create an appointment, filling in what the caller did not specify.
     *
     * property_id is resolved through a tenant-scoped lookup and the id that survives it is
     * what gets stored, so a property belonging to someone else lands as null rather than as a
     * row pointing across tenants. The staff member comes from the property unless the caller
     * names one.
     */
    public static function book(Tenant $tenant, array $attributes): Appointment
    {
        $property = null;

        if (! empty($attributes['property_id'])) {
            $property = Property::where('tenant_id', $tenant->id)->find((int) $attributes['property_id']);
        }

        $type = $attributes['appointment_type'] ?? 'showing';

        return Appointment::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property?->id,
            'staff_member_id' => $attributes['staff_member_id'] ?? $property?->staff_member_id,
            'visitor_name' => $attributes['visitor_name'],
            'visitor_email' => $attributes['visitor_email'],
            'visitor_phone' => $attributes['visitor_phone'] ?? null,
            'appointment_date' => $attributes['appointment_date'],
            'appointment_time' => ($attributes['appointment_time'] ?? null) ?: self::DEFAULT_TIME,
            'appointment_type' => in_array($type, self::TYPES, true) ? $type : 'showing',
            'notes' => $attributes['notes'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'source' => $attributes['source'],
        ]);
    }
}
