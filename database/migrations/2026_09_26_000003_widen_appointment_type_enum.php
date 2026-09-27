<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen appointments.appointment_type to the types the app actually offers.
 *
 * The admin form's dropdown lists seven: showing, consultation, inspection, open_house,
 * listing_appointment, closing, other. The column accepted four:
 * enum('showing','consultation','follow_up','other'). MySQL runs in strict mode, so choosing
 * inspection, open_house, listing_appointment or closing was a truncation error — four of the
 * seven options in that dropdown returned a 500 rather than booking anything.
 *
 * The column was the part that lagged, not the form: those are ordinary real-estate
 * appointment types and someone put them in the UI deliberately. follow_up is kept because the
 * admin assistant's tool offers it even though the form does not.
 *
 * Widening an enum cannot fail on existing rows — every value that was valid still is. Checked
 * anyway before writing this: every stored row is 'showing'.
 */
return new class extends Migration
{
    private const WIDE = "enum('showing','consultation','follow_up','inspection','open_house','listing_appointment','closing','other')";

    private const NARROW = "enum('showing','consultation','follow_up','other')";

    public function up(): void
    {
        DB::statement('ALTER TABLE appointments MODIFY appointment_type '.self::WIDE." NOT NULL DEFAULT 'showing'");
    }

    public function down(): void
    {
        // Anything outside the old set has to go somewhere; 'other' is the honest landing spot.
        DB::table('appointments')
            ->whereNotIn('appointment_type', ['showing', 'consultation', 'follow_up', 'other'])
            ->update(['appointment_type' => 'other']);

        DB::statement('ALTER TABLE appointments MODIFY appointment_type '.self::NARROW." NOT NULL DEFAULT 'showing'");
    }
};
