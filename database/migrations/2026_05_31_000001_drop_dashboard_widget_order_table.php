<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The dashboard_widget_order table was created on 2026-01-01 but
        // never wired up — widget ordering ended up living in the
        // site_settings.dashboard_config JSON column instead. Drop the
        // dead table.
        Schema::dropIfExists('dashboard_widget_order');
    }

    public function down(): void
    {
        Schema::create('dashboard_widget_order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('widget_key');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
