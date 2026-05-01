<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TRIM v MySQL odstraňuje jen mezery — REPLACE(CHAR(13/10)) odstraní skutečné CR LF bajty
        DB::statement("
            UPDATE produkty
               SET obrazek = REPLACE(REPLACE(obrazek, CHAR(13), ''), CHAR(10), '')
             WHERE LOCATE(CHAR(13), obrazek) > 0
                OR LOCATE(CHAR(10), obrazek) > 0
        ");

        // Notifikace mají data jako JSON — parsujeme v PHP, ať JSON zůstane platný
        DB::table('notifications')
            ->whereNotNull('data')
            ->orderBy('id')
            ->each(function ($row) {
                $data = json_decode($row->data, true);
                if (!is_array($data)) return;

                if (isset($data['img']) && (str_contains($data['img'], "\r") || str_contains($data['img'], "\n"))) {
                    $data['img'] = trim($data['img']);
                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void {}
};
