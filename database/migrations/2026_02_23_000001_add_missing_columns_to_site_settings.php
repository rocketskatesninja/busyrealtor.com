<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('hero_background_type', 50)->default('preset')->after('dark_mode_enabled');
            $table->string('hero_preset', 100)->nullable()->after('hero_background_type');
            $table->string('hero_image', 500)->nullable()->after('hero_preset');
            $table->string('hero_title', 300)->nullable()->after('hero_image');
            $table->string('hero_subtitle', 500)->nullable()->after('hero_title');
            $table->string('cta_primary_text', 100)->nullable()->after('hero_subtitle');
            $table->string('cta_primary_link', 300)->nullable()->after('cta_primary_text');
            $table->string('cta_secondary_text', 100)->nullable()->after('cta_primary_link');
            $table->string('cta_secondary_link', 300)->nullable()->after('cta_secondary_text');
            $table->boolean('notify_on_contact')->default(true)->after('enable_email_notifications');
            $table->boolean('notify_on_appointment')->default(true)->after('notify_on_contact');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_background_type', 'hero_preset', 'hero_image',
                'hero_title', 'hero_subtitle',
                'cta_primary_text', 'cta_primary_link',
                'cta_secondary_text', 'cta_secondary_link',
                'notify_on_contact', 'notify_on_appointment',
            ]);
        });
    }
};
