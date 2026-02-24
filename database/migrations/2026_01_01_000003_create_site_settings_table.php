<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('site_title')->nullable();
            $table->string('tagline', 500)->nullable();
            $table->text('logo_image')->nullable();
            $table->string('primary_color', 20)->default('#3B82F6');
            $table->string('secondary_color', 20)->default('#1E40AF');
            $table->string('accent_color', 20)->default('#F59E0B');
            $table->enum('header_display_mode', ['logo_only', 'text_only', 'both'])->default('both');
            $table->enum('header_mode', ['hero', 'default'])->default('hero');
            $table->string('title_font', 100)->default('Poppins');
            $table->string('body_font', 100)->default('Inter');
            $table->string('site_title_font_size', 20)->default('3xl');
            $table->string('site_title_font_weight', 10)->default('800');
            $table->string('site_title_letter_spacing', 20)->default('-0.5px');
            $table->enum('title_color_type', ['solid', 'gradient'])->default('gradient');
            $table->string('title_color_solid', 20)->default('#3B82F6');
            $table->string('title_gradient_start', 20)->default('#3B82F6');
            $table->string('title_gradient_via', 20)->default('#8B5CF6');
            $table->string('title_gradient_end', 20)->default('#1E40AF');

            $table->json('homepage_sections')->nullable();
            $table->json('features_items')->nullable();
            $table->json('services_items')->nullable();
            $table->json('testimonials_items')->nullable();
            $table->json('stats_items')->nullable();
            $table->json('faq_items')->nullable();
            $table->json('dashboard_config')->nullable();

            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('contact_address')->nullable();
            $table->text('social_facebook')->nullable();
            $table->text('social_instagram')->nullable();
            $table->text('social_twitter')->nullable();
            $table->text('social_linkedin')->nullable();

            $table->boolean('chatbot_enabled')->default(false);
            $table->string('chatbot_name', 100)->default('Assistant');
            $table->enum('chatbot_personality', ['professional', 'friendly', 'casual'])->default('professional');
            $table->integer('chatbot_expiration')->default(24);
            $table->text('chatbot_welcome')->nullable();
            $table->text('chatbot_bio')->nullable();

            $table->boolean('enable_email_notifications')->default(true);
            $table->string('notification_email')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption', 10)->default('tls');
            $table->string('smtp_from_email')->nullable();
            $table->string('smtp_from_name')->nullable();

            $table->text('site_description')->nullable();
            $table->text('default_share_image')->nullable();
            $table->string('google_site_verification')->nullable();
            $table->boolean('search_engine_visibility')->default(true);
            $table->string('timezone', 100)->default('America/New_York');
            $table->boolean('show_login_link')->default(true);
            $table->boolean('dark_mode_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
