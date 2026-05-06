<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vydej_materialu', function (Blueprint $table) {
            $table->dropForeign(['uraz_id']);
            $table->foreignId('uraz_id')->nullable()->change();
            $table->foreign('uraz_id')->references('id')->on('urazy')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vydej_materialu', function (Blueprint $table) {
            $table->dropForeign(['uraz_id']);
            $table->foreignId('uraz_id')->nullable(false)->change();
            $table->foreign('uraz_id')->references('id')->on('urazy')->cascadeOnDelete();
        });
    }
};
