<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->text('facebook_client_id')->nullable()->after('google_client_secret');
            $table->text('facebook_client_secret')->nullable()->after('facebook_client_id');
            $table->text('twitter_client_id')->nullable()->after('facebook_client_secret');
            $table->text('twitter_client_secret')->nullable()->after('twitter_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['facebook_client_id', 'facebook_client_secret', 'twitter_client_id', 'twitter_client_secret']);
        });
    }
};
