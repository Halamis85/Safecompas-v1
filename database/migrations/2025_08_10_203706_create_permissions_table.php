<?php
// database/migrations/2024_xx_xx_create_permissions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // oopp.view, oopp.create, lekarnicke.view
            $table->string('display_name'); // Zobrazit OOPP, Vytvořit OOPP
            $table->string('module'); // oopp, lekarnicke, admin, notifications
            $table->string('action'); // view, create, edit, delete, admin
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
