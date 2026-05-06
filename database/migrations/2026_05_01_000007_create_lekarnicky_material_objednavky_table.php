<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lekarnicky_material_objednavky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lekarnicky_id')->constrained('lekarnicke')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('lekarnicky_material')->nullOnDelete();
            $table->foreignId('objednal_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nazev_materialu');
            $table->string('typ_materialu')->nullable();
            $table->string('jednotka', 50)->nullable();
            $table->unsignedInteger('mnozstvi')->default(1);
            $table->string('duvod', 50)->default('manual');
            $table->string('status', 30)->default('cekajici');
            $table->timestamp('datum_objednani')->useCurrent();
            $table->timestamp('datum_objednano')->nullable();
            $table->timestamp('datum_vydano')->nullable();
            $table->text('poznamky')->nullable();
            $table->timestamps();

            $table->index(['lekarnicky_id', 'status']);
            $table->index(['material_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lekarnicky_material_objednavky');
    }
};
