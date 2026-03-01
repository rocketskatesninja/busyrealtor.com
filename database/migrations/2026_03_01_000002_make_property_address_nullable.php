<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('address_street')->nullable()->change();
            $table->string('address_city', 100)->nullable()->change();
            $table->string('address_state', 100)->nullable()->change();
            $table->string('address_zip', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('address_street')->nullable(false)->change();
            $table->string('address_city', 100)->nullable(false)->change();
            $table->string('address_state', 100)->nullable(false)->change();
            $table->string('address_zip', 20)->nullable(false)->change();
        });
    }
};
