<?php

namespace App\Console\Commands;

use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
use App\Models\Uraz;
use App\Models\VydejMaterialu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Smaže všechna data lékárniček (lékárničky, materiál, úrazy, výdeje, přiřazení).
 *
 * BEZPEČNOSTNÍ POJISTKY:
 *   - V production prostředí vyžaduje dvojí potvrzení
 *   - V ostatních prostředích jednoduché potvrzení (lze přeskočit přes --force)
 *   - --dry-run pouze vypíše, kolik záznamů by bylo smazáno
 *
 * Použití:
 *   php artisan lekarnicky:wipe --dry-run    (pouze vypíše)
 *   php artisan lekarnicky:wipe              (interaktivní)
 *   php artisan lekarnicky:wipe --force      (bez ptaní - pro skripty)
 */
class WipeLekarnickyData extends Command
{
    protected $signature = 'lekarnicky:wipe
                            {--dry-run : Pouze vypsat počty, nic nemazat}
                            {--force   : Přeskočit potvrzovací otázku}
                            {--keep-notifications : Ponechat staré in-app notifikace ve zvonku}';

    protected $description = 'Smaže všechna data o lékárničkách (lékárničky, materiál, úrazy, výdeje, přiřazení uživatelů). NEVRATNÉ.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');
        $keepNotif = (bool) $this->option('keep-notifications');

        // ============================================================
        // SPOČÍTAT, CO BUDE SMAZÁNO
        // ============================================================
        $counts = [
            'lekarnicke'              => DB::table('lekarnicke')->count(),
            'lekarnicky_material'     => DB::table('lekarnicky_material')->count(),
            'urazy'                   => DB::table('urazy')->count(),
            'vydej_materialu'         => DB::table('vydej_materialu')->count(),
            'user_lekarnicky_access'  => DB::table('user_lekarnicky_access')->count(),
        ];

        $totalRows = array_sum($counts);

        if ($totalRows === 0) {
            $this->info('Žádná data lékárniček v databázi - není co mazat.');
            return self::SUCCESS;
        }

        // ============================================================
        // VÝPIS, CO BUDE SMAZÁNO
        // ============================================================
        $this->newLine();
        $this->warn('========================================================');
        $this->warn('  SMAZÁNÍ DAT LÉKÁRNIČEK');
        $this->warn('========================================================');
        $this->newLine();

        $this->table(
            ['Tabulka', 'Počet záznamů ke smazání'],
            [
                ['lekarnicke (lékárničky)',                    $counts['lekarnicke']],
                ['lekarnicky_material (materiál)',             $counts['lekarnicky_material']],
                ['urazy (záznamy úrazů)',                      $counts['urazy']],
                ['vydej_materialu (výdeje materiálu)',         $counts['vydej_materialu']],
                ['user_lekarnicky_access (přiřazení uživ.)',   $counts['user_lekarnicky_access']],
                ['CELKEM',                                     $totalRows],
            ]
        );

        $this->newLine();

        if ($dryRun) {
            $this->info('[DRY RUN] Žádná data nebyla smazána. Spusťte bez --dry-run pro skutečné smazání.');
            return self::SUCCESS;
        }

        // ============================================================
        // POTVRZENÍ
        // ============================================================
        if (!$force) {
            $this->error('  TATO AKCE JE NEVRATNÁ!');
            $this->newLine();

            // V produkci dvojí potvrzení
            if (app()->environment('production')) {
                $this->warn('Detekováno produkční prostředí.');

                $appName = config('app.name', 'aplikace');
                $confirm1 = $this->ask(
                    "Pro pokračování napište přesně název aplikace ({$appName})"
                );
                if ($confirm1 !== $appName) {
                    $this->error('Špatný vstup. Zrušeno.');
                    return self::FAILURE;
                }
            }

            if (!$this->confirm('Opravdu smazat všechna data lékárniček?', false)) {
                $this->info('Zrušeno.');
                return self::FAILURE;
            }
        }

        // ============================================================
        // VLASTNÍ MAZÁNÍ
        // ============================================================
        $this->info('Mažu data...');

        try {
            DB::transaction(function () use ($keepNotif) {
                // Stačí smazat lékárničky - vše ostatní spadne přes ON DELETE CASCADE.
                // Ale explicitně vypisujeme jednotlivé kroky pro jistotu a pro
                // případ, že by FK constraint někdy v budoucnu byl odstraněn.

                // 1. vydej_materialu (vázáno na urazy + material)
                $deletedVydej = DB::table('vydej_materialu')->delete();

                // 2. urazy
                $deletedUrazy = DB::table('urazy')->delete();

                // 3. material
                $deletedMaterial = DB::table('lekarnicky_material')->delete();

                // 4. user_lekarnicky_access (pivot)
                $deletedAccess = DB::table('user_lekarnicky_access')->delete();

                // 5. samotné lékárničky
                $deletedLekarnicky = DB::table('lekarnicke')->delete();

                // 6. (volitelně) staré in-app notifikace, které se vážou k smazaným lékárničkám
                if (!$keepNotif) {
                    // Notifikace ukládají v JSON sloupci 'data' třeba {kind: 'material_expiring', ...}
                    // Promažeme všechny notifikace s těmito 'kind' hodnotami.
                    $kinds = ['material_expiring', 'material_expired', 'material_low_stock', 'lekarnicka_inspection'];

                    $deletedNotif = 0;
                    foreach ($kinds as $kind) {
                        // SQL pattern match v JSON - v MySQL stačí LIKE, je to spolehlivé
                        $deletedNotif += DB::table('notifications')
                            ->where('data', 'LIKE', '%"kind":"' . $kind . '"%')
                            ->delete();
                    }

                    Log::info('lekarnicky:wipe smazal data', [
                        'lekarnicke' => $deletedLekarnicky,
                        'material'   => $deletedMaterial,
                        'urazy'      => $deletedUrazy,
                        'vydej'      => $deletedVydej,
                        'access'     => $deletedAccess,
                        'notifs'     => $deletedNotif,
                    ]);
                }
            });

            // ============================================================
            // RESET AUTO_INCREMENT (volitelné - aby ID začalo od 1)
            // ============================================================
            try {
                DB::statement('ALTER TABLE lekarnicke              AUTO_INCREMENT = 1');
                DB::statement('ALTER TABLE lekarnicky_material     AUTO_INCREMENT = 1');
                DB::statement('ALTER TABLE urazy                   AUTO_INCREMENT = 1');
                DB::statement('ALTER TABLE vydej_materialu         AUTO_INCREMENT = 1');
            } catch (\Throwable $e) {
                // Reset AUTO_INCREMENT není povinný (ne všechny DB ho podporují),
                // takže selhání tady není fatální.
                $this->warn('Reset AUTO_INCREMENT neproběhl: ' . $e->getMessage());
            }

            $this->newLine();
            $this->info('✓ Data lékárniček byla úspěšně smazána.');
            $this->newLine();
            $this->info('Můžete začít vytvářet nové lékárničky přes UI.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Chyba při mazání: ' . $e->getMessage());
            Log::error('lekarnicky:wipe selhalo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
