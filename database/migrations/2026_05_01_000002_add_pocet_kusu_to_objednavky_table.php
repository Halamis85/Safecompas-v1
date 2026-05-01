<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objednavky', function (Blueprint $table) {
            $table->unsignedSmallInteger('pocet_kusu')->default(1)->after('velikost');
        });
    }

    public function down(): void
    {
        Schema::table('objednavky', function (Blueprint $table) {
            $table->dropColumn('pocet_kusu');
        });
    }
};
