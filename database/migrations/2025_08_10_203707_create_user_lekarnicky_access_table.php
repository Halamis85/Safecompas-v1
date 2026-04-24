<?php
// database/migrations/2024_xx_xx_create_user_lekarnicky_access_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_lekarnicky_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lekarnicky_id')->constrained('lekarnicke')->onDelete('cascade');
            $table->enum('access_level', ['view', 'edit', 'admin'])->default('view');
            $table->timestamps();

            $table->unique(['user_id', 'lekarnicky_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_lekarnicky_access');
    }
};
