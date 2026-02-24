<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('integration_type', 50);
            $table->text('api_key')->nullable();
            $table->string('provider', 100)->nullable();
            $table->json('config')->nullable();
            $table->string('webhook_token', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'integration_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
