<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'title')) {
                $table->string('title', 300)->nullable()->after('id');
            }
            if (!Schema::hasColumn('properties', 'half_baths')) {
                $table->tinyInteger('half_baths')->nullable()->after('bathrooms');
            }
        });
    }
    public function down(): void {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['title', 'half_baths']);
        });
    }
};
