<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE site_settings MODIFY COLUMN header_display_mode ENUM('logo_only','text_only','both','favicon_only','favicon_text') NOT NULL DEFAULT 'both'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE site_settings MODIFY COLUMN header_display_mode ENUM('logo_only','text_only','both') NOT NULL DEFAULT 'both'");
    }
};
