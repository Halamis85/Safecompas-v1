<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produkty', function (Blueprint $table) {
            $table->boolean('allow_quantity')->default(false)->after('cod_produktu');
        });
    }

    public function down(): void
    {
        Schema::table('produkty', function (Blueprint $table) {
            $table->dropColumn('allow_quantity');
        });
    }
};
