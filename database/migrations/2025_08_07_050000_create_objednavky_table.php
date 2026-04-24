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
        Schema::create('objednavky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zamestnanec_id')->constrained('zamestnanci')->onDelete('cascade');
            $table->foreignId('produkt_id')->constrained('produkty')->onDelete('cascade');
            $table->string('velikost');
            $table->enum('status', ['cekajici', 'Objednano', 'vydano'])->default('cekajici');
            $table->date('datum_objednani');
            $table->datetime('datum_vydani')->nullable();
            $table->string('podpis_path')->nullable();
            $table->enum('email_send', ['1', '0'])->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objednavky');
    }
};
