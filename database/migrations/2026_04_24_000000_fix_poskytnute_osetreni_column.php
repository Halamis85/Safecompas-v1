<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jen pokud existuje starý sloupec s cyrilským "е"
        if (Schema::hasColumn('urazy', 'poskytnutе_osetreni')
            && !Schema::hasColumn('urazy', 'poskytnute_osetreni')) {

            Schema::table('urazy', function (Blueprint $table) {
                $table->renameColumn('poskytnutе_osetreni', 'poskytnute_osetreni');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('urazy', 'poskytnute_osetreni')
            && !Schema::hasColumn('urazy', 'poskytnutе_osetreni')) {

            Schema::table('urazy', function (Blueprint $table) {
                $table->renameColumn('poskytnute_osetreni', 'poskytnutе_osetreni');
            });
        }
    }
};