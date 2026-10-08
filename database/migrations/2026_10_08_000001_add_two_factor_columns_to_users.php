<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns rather than a table: a user has one live code at a time, and this follows the
 * failed_login_attempts / locked_until pattern already on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hashed, never the code itself. A readable column here would hand anyone with
            // database access a second factor that is not second at all.
            $table->string('two_factor_code_hash')->nullable()->after('locked_until');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code_hash');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_code_hash', 'two_factor_expires_at', 'two_factor_attempts']);
        });
    }
};
