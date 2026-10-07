<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FR-4: durable per-account lockout state — survives IP rotation.
            $table->unsignedInteger('failed_login_count')->default(0)->after('remember_token');
            $table->timestamp('locked_until')->nullable()->after('failed_login_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_count', 'locked_until']);
        });
    }
};
