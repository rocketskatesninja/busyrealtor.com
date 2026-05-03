<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tracks platform-SMTP piggyback usage per tenant during trial.
 * Tenants who configure their own SMTP integration bypass these
 * counters entirely — caps only apply when we're sending mail
 * on the tenant's behalf through OUR Mailgun/SES.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('piggyback_emails_today')->default(0)->after('payment_failed_at');
            $table->date('piggyback_emails_today_date')->nullable()->after('piggyback_emails_today');
            $table->unsignedInteger('piggyback_emails_total')->default(0)->after('piggyback_emails_today_date');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'piggyback_emails_today',
                'piggyback_emails_today_date',
                'piggyback_emails_total',
            ]);
        });
    }
};
