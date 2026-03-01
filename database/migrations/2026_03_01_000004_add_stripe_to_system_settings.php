<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->text('stripe_key')->nullable()->after('lock_message');
            $table->text('stripe_secret')->nullable()->after('stripe_key');
            $table->text('stripe_webhook_secret')->nullable()->after('stripe_secret');
            $table->string('stripe_starter_price_id')->nullable()->after('stripe_webhook_secret');
            $table->string('stripe_pro_price_id')->nullable()->after('stripe_starter_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'stripe_starter_price_id', 'stripe_pro_price_id']);
        });
    }
};
