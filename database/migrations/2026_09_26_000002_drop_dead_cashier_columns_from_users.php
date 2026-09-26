<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the Cashier columns left on users when billing moved to tenants.
 *
 * Tenant is the billable model (use Billable) and User is not — it has not been for some
 * time. These four columns have been carried along since, unreferenced anywhere in app/ or
 * resources/, and holding nothing: verified 0 non-null values for each on staging.
 *
 * Re-check that on prod before deploying. It is two accounts there and both are the
 * owner's, so it is very unlikely to differ, but a drop is not reversible with its data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });
    }
};
