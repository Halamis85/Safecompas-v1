<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('notify_oopp_order')->default(false)->after('is_active');
        });

        // Aktivujeme notifikace pro role, které je dosud dostávaly (hardcoded v controlleru)
        DB::table('roles')
            ->whereIn('name', ['super_admin', 'admin', 'oopp.edit'])
            ->update(['notify_oopp_order' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('notify_oopp_order');
        });
    }
};
