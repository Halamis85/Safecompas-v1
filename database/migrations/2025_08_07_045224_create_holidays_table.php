<?php
// database/migrations/xxxx_create_holidays_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->nullable(); // Pro konkrétní data
            $table->string('pattern')->nullable(); // Pro dynamické výpočty
            $table->string('country_code', 2)->default('CZ');
            $table->string('type')->nullable();
            $table->boolean('is_public_holiday')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_dynamic')->default(false); // Označí dynamické svátky
            $table->timestamps();

            $table->index(['country_code', 'pattern']);
            $table->index(['country_code', 'is_dynamic']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
