<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Tracks which trial reminder emails have been sent (e.g. [7, 3, 1])
            $table->json('trial_reminders_sent')->nullable()->after('trial_ends_at');
            // Set when invoice.payment_failed arrives; used for dunning escalation
            $table->timestamp('payment_failed_at')->nullable()->after('trial_reminders_sent');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['trial_reminders_sent', 'payment_failed_at']);
        });
    }
};
