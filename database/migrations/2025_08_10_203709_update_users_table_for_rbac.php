<?php
// database/migrations/2024_xx_xx_update_users_table_for_rbac.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Zachováme starý sloupec 'role' pro zpětnou kompatibilitu
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login')->nullable()->after('is_active');
            $table->json('preferences')->nullable()->after('last_login'); // Uživatelské preference
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login', 'preferences']);
        });
    }
};
