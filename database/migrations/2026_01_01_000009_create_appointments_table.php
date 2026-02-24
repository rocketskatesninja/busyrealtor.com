<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('staff_member_id')->nullable();
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone', 50)->nullable();
            $table->enum('appointment_type', ['showing', 'consultation', 'follow_up', 'other'])->default('showing');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->enum('source', ['website', 'chatbot', 'phone', 'other'])->default('website');
            $table->string('visitor_ip', 45)->nullable();
            $table->string('confirmation_token', 64)->nullable();
            $table->timestamp('token_expires')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
