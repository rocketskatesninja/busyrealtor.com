<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Removes 'featured' as a listing_status enum value. The Featured concept
 * is owned solely by the is_featured boolean column going forward — having
 * it BOTH as a status and a flag created two sources of truth that didn't
 * agree across the codebase (the homepage section accepted both, but the
 * gallery and map only accepted 'active'/'pending', leaving any property
 * with status='featured' invisible on those pages).
 *
 * Migration steps:
 *   1) Re-home any existing rows with status='featured' to status='active'
 *      and set is_featured=true so they keep showing on the homepage.
 *   2) Drop 'featured' from the enum (raw ALTER — Laravel's Schema builder
 *      doesn't have first-class enum modify support).
 *
 * After this:
 *   - listing_status = pure lifecycle: draft/active/pending/sold/off-market/withdrawn
 *   - is_featured     = orthogonal homepage-highlight flag
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('properties')->where('listing_status', 'featured')->update([
            'listing_status' => 'active',
            'is_featured'    => true,
        ]);

        DB::statement("ALTER TABLE properties MODIFY listing_status ENUM('active','pending','sold','withdrawn','off-market') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Just re-add 'featured' to the enum so old rows could be set back
        // to that status if needed. We don't restore the original data —
        // that information is lost, and is_featured=true preserves intent.
        DB::statement("ALTER TABLE properties MODIFY listing_status ENUM('active','pending','sold','featured','withdrawn','off-market') NOT NULL DEFAULT 'active'");
    }
};
