<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lekarnicke', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('umisteni');
            $table->string('zodpovedna_osoba');
            $table->text('popis')->nullable();
            $table->enum('status', ['aktivni', 'neaktivni', 'revize'])->default('aktivni');
            $table->date('posledni_kontrola')->nullable();
            $table->date('dalsi_kontrola')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lekarnicke');
    }
};

