<?php

namespace App\Console\Commands;

use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
use App\Models\User;
use App\Notifications\LekarnickaKontrolaBlizi;
use App\Notifications\MaterialExpired;
use App\Notifications\MaterialExpiringSoon;
use App\Notifications\MaterialNizkyStav;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Denní kontrola stavu lékárniček a odeslání notifikací.
 *
 * Spouští se přes Laravel scheduler (viz routes/console.php).
 * Pro testování bez odesílání: php artisan lekarnicky:send-notifications --dry-run
 */
class SendDailyHealthcareNotifications extends Command
{
    protected $signature = 'lekarnicky:send-notifications
                            {--dry-run : Pouze vypsat, neposílat}';

    protected $description = 'Denně rozesílá notifikace o expirujícím/expirovaném materiálu, nízkém stavu a blížících se kontrolách lékárniček.';

    /**
     * Kolik dní dopředu varovat před expirací materiálu.
     */
    private const EXPIRATION_WARN_DAYS = 30;

    /**
     * Kolik dní dopředu varovat před plánovanou kontrolou lékárničky.
     */
    private const KONTROLA_WARN_DAYS = 7;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now()->startOfDay();

        $stats = [
            'expiring' => 0,
            'expired'  => 0,
            'lowStock' => 0,
            'kontrola' => 0,
            'recipients_total' => 0,
        ];

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Začínám kontrolu lékárniček...');

        // ============================================================
        // 1. MATERIÁLY EXPIRUJÍCÍ V DOHLEDNÉ DOBĚ
        // ============================================================
        $expirujici = LekarnickeMaterial::with('lekarnicky')
            ->whereNotNull('datum_expirace')
            ->whereDate('datum_expirace', '>=', $now)
            ->whereDate('datum_expirace', '<=', $now->copy()->addDays(self::EXPIRATION_WARN_DAYS))
            ->get();

        foreach ($expirujici as $material) {
            if (!$material->lekarnicky) continue;

            $recipients = $this->recipientsFor($material->lekarnicky);
            if ($recipients->isEmpty()) continue;

            $this->dispatch($recipients, new MaterialExpiringSoon($material), $dryRun,
                "Expiruje brzy: {$material->nazev_materialu} (lékárnička {$material->lekarnicky->nazev})");

            $stats['expiring']++;
            $stats['recipients_total'] += $recipients->count();
        }

        // ============================================================
        // 2. MATERIÁLY UŽ EXPIROVANÉ
        // ============================================================
        $expirovane = LekarnickeMaterial::with('lekarnicky')
            ->whereNotNull('datum_expirace')
            ->whereDate('datum_expirace', '<', $now)
            ->get();

        foreach ($expirovane as $material) {
            if (!$material->lekarnicky) continue;

            $recipients = $this->recipientsFor($material->lekarnicky);
            if ($recipients->isEmpty()) continue;

            $this->dispatch($recipients, new MaterialExpired($material), $dryRun,
                "EXPIROVÁNO: {$material->nazev_materialu} (lékárnička {$material->lekarnicky->nazev})");

            $stats['expired']++;
            $stats['recipients_total'] += $recipients->count();
        }

        // ============================================================
        // 3. NÍZKÝ STAV MATERIÁLU
        // ============================================================
        $nizkyStav = LekarnickeMaterial::with('lekarnicky')
            ->whereColumn('aktualni_pocet', '<', 'minimalni_pocet')
            ->get();

        foreach ($nizkyStav as $material) {
            if (!$material->lekarnicky) continue;

            $recipients = $this->recipientsFor($material->lekarnicky);
            if ($recipients->isEmpty()) continue;

            $this->dispatch($recipients, new MaterialNizkyStav($material), $dryRun,
                "Nízký stav: {$material->nazev_materialu} (lékárnička {$material->lekarnicky->nazev})");

            $stats['lowStock']++;
            $stats['recipients_total'] += $recipients->count();
        }

        // ============================================================
        // 4. LÉKÁRNIČKY S BLÍŽÍCÍ SE KONTROLOU
        // ============================================================
        $kontroly = Lekarnicky::whereNotNull('dalsi_kontrola')
            ->whereDate('dalsi_kontrola', '<=', $now->copy()->addDays(self::KONTROLA_WARN_DAYS))
            ->get();

        foreach ($kontroly as $lekarnicky) {
            $recipients = $this->recipientsFor($lekarnicky);
            if ($recipients->isEmpty()) continue;

            $this->dispatch($recipients, new LekarnickaKontrolaBlizi($lekarnicky), $dryRun,
                "Kontrola se blíží: {$lekarnicky->nazev}");

            $stats['kontrola']++;
            $stats['recipients_total'] += $recipients->count();
        }

        // ============================================================
        // SOUHRN
        // ============================================================
        $summary = sprintf(
            '%sZpracováno: %d expirujících, %d expirovaných, %d nízký stav, %d kontroly. Celkem příjemců: %d',
            $dryRun ? '[DRY RUN] ' : '',
            $stats['expiring'],
            $stats['expired'],
            $stats['lowStock'],
            $stats['kontrola'],
            $stats['recipients_total']
        );

        $this->info($summary);

        if (!$dryRun) {
            Log::info('lekarnicky:send-notifications dokončeno', $stats);
        }

        return self::SUCCESS;
    }

    /**
     * Najde uživatele, kteří mají dostávat notifikace o lékárničce.
     *
     * Pravidlo:
     *   1. Všichni aktivní super_admins
     *   2. Uživatelé s lekarnickAccess (edit nebo admin) k této lékárničce
     *
     * @return Collection<\App\Models\User>
     */
    private function recipientsFor(Lekarnicky $lekarnicky): Collection
    {
        $superAdmins = User::select('id', 'email', 'firstname', 'lastname')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->get();

        $accessUsers = User::select('id', 'email', 'firstname', 'lastname')
            ->where('is_active', true)
            ->whereHas('lekarnickAccess', function ($q) use ($lekarnicky) {
                $q->where('user_lekarnicky_access.lekarnicky_id', $lekarnicky->id)
                  ->whereIn('user_lekarnicky_access.access_level', ['edit', 'admin']);
            })
            ->get();

        return $superAdmins->concat($accessUsers)->unique('id')->values();
    }

    /**
     * Bezpečné odeslání notifikace s logováním v dry-run režimu.
     */
    private function dispatch(Collection $recipients, $notification, bool $dryRun, string $description): void
    {
        if ($dryRun) {
            $this->line("  → {$description} ({$recipients->count()} příjemců)");
            return;
        }

        try {
            Notification::send($recipients, $notification);
        } catch (\Throwable $e) {
            // Selhání jedné notifikace nesmí zastavit ostatní
            Log::warning('Notifikace lékárničky se nepodařilo odeslat', [
                'description'      => $description,
                'recipients_count' => $recipients->count(),
                'error'            => $e->getMessage(),
            ]);
            $this->warn("  ! Selhalo: {$description} ({$e->getMessage()})");
        }
    }
}
