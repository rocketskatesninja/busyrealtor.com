<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('staff_member_id')->nullable();

            $table->enum('listing_status', ['active', 'pending', 'sold', 'featured', 'withdrawn'])->default('active');
            $table->enum('property_type', ['house', 'condo', 'townhouse', 'land', 'commercial', 'multi_family', 'other'])->default('house');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('address_street');
            $table->string('address_city', 100);
            $table->string('address_state', 100);
            $table->string('address_zip', 20);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->tinyInteger('bedrooms')->unsigned()->nullable();
            $table->decimal('bathrooms', 4, 1)->nullable();
            $table->integer('square_feet')->nullable();
            $table->string('lot_size', 100)->nullable();
            $table->smallInteger('year_built')->unsigned()->nullable();
            $table->tinyInteger('stories')->unsigned()->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'needs_work'])->nullable();
            $table->string('school_district')->nullable();
            $table->decimal('hoa_fee', 8, 2)->nullable();
            $table->tinyInteger('garage')->unsigned()->default(0);

            $table->boolean('has_pool')->default(false);
            $table->boolean('has_basement')->default(false);
            $table->boolean('has_fireplace')->default(false);
            $table->boolean('near_school')->default(false);
            $table->boolean('near_hospital')->default(false);
            $table->boolean('near_shopping')->default(false);
            $table->boolean('near_transit')->default(false);

            $table->text('description')->nullable();
            $table->string('mls_number', 50)->nullable();
            $table->text('virtual_tour_url')->nullable();
            $table->text('video_tour_url')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->json('amenities')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
