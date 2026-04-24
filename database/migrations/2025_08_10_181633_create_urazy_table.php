<?php
// database/migrations/2024_xx_xx_create_urazy_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('urazy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zamestnanec_id')->constrained('zamestnanci')->onDelete('cascade');
            $table->foreignId('lekarnicky_id')->constrained('lekarnicke')->onDelete('cascade');
            $table->datetime('datum_cas_urazu');
            $table->text('popis_urazu');
            $table->string('misto_urazu');
            $table->enum('zavaznost', ['lehky', 'stredni', 'tezky']);
            $table->text('poskytnutе_osetreni');
            $table->string('osoba_poskytujici_pomoc');
            $table->boolean('prevezen_do_nemocnice')->default(false);
            $table->text('poznamky')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('urazy');
    }
};
