<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL umí LOCATE(), SQLite v testech ne. Tady je objem dat malý,
        // takže portable PHP průchod je čitelnější a bezpečný pro oba drivery.
        DB::table('produkty')
            ->whereNotNull('obrazek')
            ->orderBy('id')
            ->each(function ($row) {
                if (!str_contains($row->obrazek, "\r") && !str_contains($row->obrazek, "\n")) {
                    return;
                }

                DB::table('produkty')
                    ->where('id', $row->id)
                    ->update(['obrazek' => str_replace(["\r", "\n"], '', $row->obrazek)]);
            });

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
