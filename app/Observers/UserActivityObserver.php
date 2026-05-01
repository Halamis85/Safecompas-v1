<?php

// Zapisuje vytvoření / úpravu / smazání do tabulky user_activity.

namespace App\Observers;

use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class UserActivityObserver
{
    /**
     * Sloupce, které se NIKDY neuloží do auditu (citlivé údaje).
     * Aplikuje se globálně bez ohledu na model.
     */
    protected array $hiddenAttributes = [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Sloupce, jejichž změna sama o sobě NESPUSTÍ audit záznam
     * (běžně jen automatický timestamp - bez business významu).
     */
    protected array $ignoredOnUpdate = [
        'updated_at',
        'last_login',          // logujeme jiným způsobem v AuthControlleru
        'remember_token',
    ];

    public function created(Model $model): void
    {
        $this->log(
            'created',
            $model,
            null,
            $this->cleanAttributes($model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes)) {
            return;
        }

        // Odfiltrovat změny, které samy o sobě nemají business význam
        $significant = Arr::except($changes, $this->ignoredOnUpdate);
        if (empty($significant)) {
            return;
        }

        // Pro starou hodnotu vezmeme jen klíče, které se reálně změnily
        $original = [];
        foreach (array_keys($significant) as $key) {
            $original[$key] = $model->getOriginal($key);
        }

        $this->log(
            'updated',
            $model,
            $this->cleanAttributes($original),
            $this->cleanAttributes($significant)
        );
    }

    public function deleted(Model $model): void
    {
        $this->log(
            'deleted',
            $model,
            $this->cleanAttributes($model->getOriginal()),
            null
        );
    }

    /**
     * Zachycení obnovení záznamu (soft delete restore - až bude implementován).
     */
    public function restored(Model $model): void
    {
        $this->log(
            'restored',
            $model,
            null,
            $this->cleanAttributes($model->getAttributes())
        );
    }

    /**
     * Zápis do user_activity. Loguje neselhávajícím způsobem -
     * pokud audit selže, hlavní operace nesmí být zablokována.
     */
    protected function log(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        try {
            $userId = $this->resolveUserId();
            if ($userId === null) {
                // Bez identifikovaného uživatele neukládáme - jinak by user_id constraint padl.
                // Týká se seederů, console příkazů, jobs bez kontextu apod.
                return;
            }

            UserActivity::create([
                'user_id'    => $userId,
                'action'     => $action,
                'table_name' => $model->getTable(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $this->resolveIp(),
                'user_agent' => $this->resolveUserAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit nesmí blokovat hlavní operaci - jen logujeme do souborového logu
            Log::warning('UserActivity audit selhal', [
                'action'  => $action,
                'model'   => get_class($model),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Odstraní citlivé sloupce z atributů.
     */
    protected function cleanAttributes(array $attributes): array
    {
        return Arr::except($attributes, $this->hiddenAttributes);
    }

    /**
     * Aplikace používá vlastní session-based auth (nikoliv Auth fasádu),
     * proto zjišťujeme uživatele přes session('user.id').
     *
     * Pokud session neexistuje (CLI příkazy, jobs), vrátíme null.
     */
    protected function resolveUserId(): ?int
    {
        try {
            if (!app()->bound('session')) {
                return null;
            }

        $sessionUser = session('user');
        if (is_array($sessionUser) && !empty($sessionUser['id'])) {
            return (int) $sessionUser['id'];
        }
    } catch (\Throwable $e) {
        // Session nedostupná - např. v console příkazech
    }

    return null;
}

    protected function resolveIp(): ?string
    {
        try {
            return request()?->ip();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveUserAgent(): ?string
    {
        try {
            $ua = request()?->userAgent();
            return $ua ? mb_substr($ua, 0, 500) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
