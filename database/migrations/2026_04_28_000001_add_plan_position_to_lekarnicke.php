<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Přidá sloupce pro pozici lékárničky na plánu budovy.
     * Hodnoty jsou v procentech 0-100 (relativní k rozměrům obrázku),
     * takže fungují i když se obrázek za rok vymění za jiné rozlišení.
     */
    public function up(): void
    {
        Schema::table('lekarnicke', function (Blueprint $table) {
            $table->decimal('plan_x', 5, 2)->nullable()->after('dalsi_kontrola');
            $table->decimal('plan_y', 5, 2)->nullable()->after('plan_x');
        });
    }

    public function down(): void
    {
        Schema::table('lekarnicke', function (Blueprint $table) {
            $table->dropColumn(['plan_x', 'plan_y']);
        });
    }
};
