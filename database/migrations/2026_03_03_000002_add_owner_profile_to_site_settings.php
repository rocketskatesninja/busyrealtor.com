<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("site_settings", function (Blueprint $table) {
            $table->string("owner_name")->nullable()->after("chatbot_bio");
            $table->text("owner_photo")->nullable()->after("owner_name");
            $table->text("owner_bio")->nullable()->after("owner_photo");
        });
    }

    public function down(): void
    {
        Schema::table("site_settings", function (Blueprint $table) {
            $table->dropColumn(["owner_name", "owner_photo", "owner_bio"]);
        });
    }
};
