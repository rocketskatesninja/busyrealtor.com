<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->decimal('starter_price', 8, 2)->default(29.00)->after('stripe_pro_price_id');
            $table->decimal('pro_price', 8, 2)->default(59.00)->after('starter_price');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['starter_price', 'pro_price']);
        });
    }
};
