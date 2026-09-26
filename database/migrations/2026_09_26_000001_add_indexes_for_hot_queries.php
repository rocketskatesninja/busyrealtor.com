<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries that run on every page, and uniqueness for two tables that are
 * already treated as if they had it.
 *
 * Measured before this ran, on staging:
 *   views_month        type=ALL key=NULL  Using where
 *   views_30day_chart  type=ALL key=NULL  Using where; Using temporary; Using filesort
 *   gallery            type=ALL key=NULL  Using where; Using filesort
 *
 * Every table here already has a single-column index on tenant_id from its foreign key,
 * which is why some of these queries reached 'ref' and the rest did not: the moment a
 * second column is filtered or sorted, that index stops being enough.
 *
 * The two unique indexes were checked for existing violations first — on staging here, and
 * they must be checked on prod before this is deployed, because a unique index will not
 * build over duplicate rows and the row that would have to go might be the demo tenant's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_views', function (Blueprint $table) {
            // Both dashboard aggregates filter tenant_id and a viewed_at window, and the
            // 30-day chart then groups by date. Without this it is a full scan of every
            // pageview the platform has ever recorded, for every dashboard load.
            $table->index(['tenant_id', 'viewed_at'], 'property_views_tenant_viewed_index');
        });

        Schema::table('properties', function (Blueprint $table) {
            // The public gallery: where tenant_id and listing_status, order by created_at.
            $table->index(['tenant_id', 'listing_status', 'created_at'], 'properties_tenant_status_created_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            // The unread count in the admin view composer, which runs on every admin render.
            $table->index(['tenant_id', 'is_read', 'created_at'], 'messages_tenant_read_created_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            // The upcoming-appointments count, same view composer.
            $table->index(['tenant_id', 'status', 'appointment_date'], 'appointments_tenant_status_date_index');

            // A public, unauthenticated lookup by token had no index at all, and nothing
            // guaranteed the token was distinct — two rows sharing one would have let a
            // visitor confirm or cancel someone else's appointment. NULL is still allowed
            // as many times as it likes, which is what rows created before tokens need.
            $table->unique('confirmation_token', 'appointments_confirmation_token_unique');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            // Every reader does ->where('tenant_id', ...)->first() and every writer assumes
            // one row. Two rows means writes land on one and reads on the other, which
            // presents as "my settings won't save". legal_pages already has this constraint.
            $table->unique('tenant_id', 'site_settings_tenant_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('property_views', function (Blueprint $table) {
            $table->dropIndex('property_views_tenant_viewed_index');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_tenant_status_created_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_tenant_read_created_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_tenant_status_date_index');
            $table->dropUnique('appointments_confirmation_token_unique');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropUnique('site_settings_tenant_id_unique');
        });
    }
};
