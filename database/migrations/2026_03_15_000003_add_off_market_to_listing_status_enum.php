<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN listing_status ENUM('active','pending','sold','featured','withdrawn','off-market') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN listing_status ENUM('active','pending','sold','featured','withdrawn') NOT NULL DEFAULT 'active'");
    }
};
