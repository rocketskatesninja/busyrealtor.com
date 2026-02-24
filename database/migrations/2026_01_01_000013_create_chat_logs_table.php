<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('session_id', 100);
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->timestamps();
            $table->index(['tenant_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};
