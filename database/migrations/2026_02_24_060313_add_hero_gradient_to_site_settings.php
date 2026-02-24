<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('site_settings', 'hero_gradient_start')) {
                $table->string('hero_gradient_start', 20)->nullable()->after('hero_image');
            }
            if (!Schema::hasColumn('site_settings', 'hero_gradient_end')) {
                $table->string('hero_gradient_end', 20)->nullable()->after('hero_gradient_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['hero_gradient_start', 'hero_gradient_end']);
        });
    }
};
