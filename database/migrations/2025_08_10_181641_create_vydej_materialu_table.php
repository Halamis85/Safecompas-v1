<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vydej_materialu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uraz_id')->constrained('urazy')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('lekarnicky_material')->onDelete('cascade');
            $table->integer('vydane_mnozstvi');
            $table->string('jednotka');
            $table->datetime('datum_vydeje');
            $table->string('osoba_vydavajici');
            $table->text('poznamky')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vydej_materialu');
    }
};
