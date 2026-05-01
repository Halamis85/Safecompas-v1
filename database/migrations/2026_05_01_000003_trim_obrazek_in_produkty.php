<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE produkty SET obrazek = TRIM(obrazek) WHERE obrazek != TRIM(obrazek)");
    }

    public function down(): void
    {
        // Trim je nevratná operace — původní data s \r\n nelze obnovit
    }
};
