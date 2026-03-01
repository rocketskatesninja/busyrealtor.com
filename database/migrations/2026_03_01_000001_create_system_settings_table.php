<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('registrations_enabled')->default(true);
            $table->boolean('site_locked')->default(false);
            $table->text('lock_message')->nullable();
            $table->timestamps();
        });

        // Seed the single settings row
        DB::table('system_settings')->insert([
            'registrations_enabled' => true,
            'site_locked'           => false,
            'lock_message'          => 'We are currently performing maintenance. Please check back soon.',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
