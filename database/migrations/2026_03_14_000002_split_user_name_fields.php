<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id')->default('');
            $table->string('last_name')->after('first_name')->default('');
        });

        // Migrate existing data: first word → first_name, rest → last_name
        DB::statement("UPDATE users SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = TRIM(SUBSTR(name, LOCATE(' ', name) + 1)) WHERE name IS NOT NULL AND name != ''");
        // Fix last_name for single-word names (LOCATE returns 0, so last_name gets the full name)
        DB::statement("UPDATE users SET last_name = '' WHERE name IS NOT NULL AND LOCATE(' ', name) = 0");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
        });

        DB::statement("UPDATE users SET name = CONCAT(first_name, ' ', last_name)");
        DB::statement("UPDATE users SET name = TRIM(name)");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
