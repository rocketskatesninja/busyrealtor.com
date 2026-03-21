<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->json('nearby_places_cache')->nullable()->after('amenities');
            $table->timestamp('nearby_places_fetched_at')->nullable()->after('nearby_places_cache');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['nearby_places_cache', 'nearby_places_fetched_at']);
        });
    }
};
