<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sessions in the database rather than in files.
     *
     * The file drivers were the last thing on this app still writing state to disk, and
     * that cost us a 500 on the admin dashboard: the file cache shards keys into
     * subdirectories, ten of which had been created by a CLI user months earlier and could
     * not be written to by the web server. Whether a request worked depended on which
     * bucket its key happened to hash into.
     *
     * Sessions carry the same risk with a worse failure — everybody logged out at once
     * rather than one screen erroring — so both move together. The database is already
     * there, already backed up, and already what every other app on this host uses.
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
