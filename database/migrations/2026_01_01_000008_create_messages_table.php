<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('sender_name');
            $table->string('sender_email');
            $table->string('sender_phone', 50)->nullable();
            $table->text('message');
            $table->enum('source', ['contact_form', 'chatbot', 'phone', 'other'])->default('contact_form');
            $table->enum('status', ['new', 'read', 'replied', 'archived', 'spam'])->default('new');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
