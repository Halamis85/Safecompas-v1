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
        Schema::create('produkty', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('obrazek')->nullable();
            $table->text('dostupne_velikosti')->nullable();
            $table->foreignId('druh_id')->constrained('druhy_oopp')->onDelete('cascade');
            $table->decimal('cena')->nullable();
            $table->string('cod_produktu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produkty');
    }
};
