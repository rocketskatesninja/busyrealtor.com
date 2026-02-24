<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widget_order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('section', 50);
            $table->string('widget_key', 100);
            $table->integer('sort_order')->default(0);
            $table->index(['tenant_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widget_order');
    }
};
