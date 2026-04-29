<?php

//   - registraci CspNonce singletonu
//   - propisování nonce do <script> tagů generovaných direktivou @vite

namespace App\Providers;

use App\Models\Contact;
use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
use App\Models\Objednavka;
use App\Models\Permission;
use App\Models\Produkt;
use App\Models\Role;
use App\Models\Uraz;
use App\Models\User;
use App\Models\VydejMaterialu;
use App\Models\Zamestnanec;
use App\Observers\UserActivityObserver;
use App\Support\CspNonce;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

require_once app_path('Helpers/ViewHelper.php');

class AppServiceProvider extends ServiceProvider
{
    /**
     * Modely, u kterých chceme audit log (#5 z analýzy).
     */
    protected array $auditedModels = [
        User::class,
        Role::class,
        Permission::class,
        Zamestnanec::class,
        Produkt::class,
        Objednavka::class,
        Lekarnicky::class,
        LekarnickeMaterial::class,
        Uraz::class,
        VydejMaterialu::class,
        Contact::class,
    ];

    public function register(): void
    {
        // Singleton - jeden nonce na request, sdílený mezi
        // SecurityHeaders middleware, AppServiceProvider boot (Vite tagy)
        // a případnými Blade direktivami `{{ csp_nonce() }}`.
        $this->app->singleton(CspNonce::class);
    }

    public function boot(): void
    {
        // === 1) Audit observers ===
        foreach ($this->auditedModels as $model) {
            $model::observe(UserActivityObserver::class);
        }

        // === 2) CSP nonce do tagů generovaných @vite ===
        // Vite (laravel-vite-plugin) renderuje <script type="module" src="..."></script>
        // a <link rel="stylesheet" ...>. Aby skripty přežily naše CSP `script-src`
        // (žádné 'unsafe-inline'), musí mít nonce atribut.
        //
        // useCspNonce je nejčistší cesta - Laravel ho propíše do všech tagů
        // generovaných @vite a zároveň ho zpřístupní přes Vite::cspNonce().
        $nonce = $this->app->make(CspNonce::class)->get();
        Vite::useCspNonce($nonce);
    }
}
