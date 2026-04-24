<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lekarnicky_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lekarnicky_id')->constrained('lekarnicke')->onDelete('cascade');
            $table->string('nazev_materialu');
            $table->string('typ_materialu'); // obvaz, dezinfekce, tablety, atd.
            $table->integer('aktualni_pocet');
            $table->integer('minimalni_pocet');
            $table->integer('maximalni_pocet');
            $table->string('jednotka'); // ks, ml, g, atd.
            $table->date('datum_expirace')->nullable();
            $table->decimal('cena_za_jednotku', 8, 2)->nullable();
            $table->string('dodavatel')->nullable();
            $table->text('poznamky')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lekarnicky_material');
    }
};
