<?php
// app/Http/Controllers/LekarnickController.php

namespace App\Http\Controllers;

use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
use App\Models\LekarnickyMaterialObjednavka;
use App\Models\Uraz;
use App\Models\VydejMaterialu;
use App\Models\Zamestnanec;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreLekarnickyRequest;
use App\Http\Requests\UpdateLekarnickyRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Requests\StoreUrazRequest;
use App\Http\Requests\VydejMaterialRequest;

class LekarnickController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Přehled lékárniček',
            'descriptions' => 'Správa a sledování lékárniček',
            'canGlobalManageMaterial' => $this->canManageMaterialGlobally(),
        ];
        return view('lekarnicky.index', $data);
    }

    public function admin()
    {
        $perms = session('user.permissions', []);
        $isAllowed = $this->canManageMaterialGlobally()
            || session('user.is_super_admin')
            || in_array('lekarnicke.create', $perms);

        abort_unless($isAllowed, 403, 'Nemáte oprávnění k administraci lékárniček.');

        return view('lekarnicky.admin', [
            'title' => 'Administrace lékárniček',
            'descriptions' => 'Přidávání lékárniček a nových materiálových položek',
            'canGlobalManageMaterial' => $this->canManageMaterialGlobally(),
        ]);
    }
    public function dashboard()
    {
        // Místo iterace přes N lékárniček a volání accessoru pro každou
        // jdou všechny statistiky 4 agregačními dotazy.
        $now = now();
        $expirationLimit = $now->copy()->addDays(config('lekarnicke.material_expiration_warn_days', 30));
        $kontrolaLimit   = $now->copy()->addDays(config('lekarnicke.kontrola_warn_days', 7));

        // 1) Lékárničky - běžný uživatel vidí jen přidělené lékárničky.
        $lekarnicke = $this->scopedLekarnickyQuery()
            ->with('material')
            ->get();
        $lekarnickyIds = $lekarnicke->pluck('id');

        // 2) Materiál - dva dotazy nad celou tabulkou (cca 1 ms i pro 100k řádků s indexy)
        $expirujici = LekarnickeMaterial::query()
            ->whereIn('lekarnicky_id', $lekarnickyIds)
            ->whereNotNull('datum_expirace')
            ->where('datum_expirace', '>=', $now)
            ->where('datum_expirace', '<=', $expirationLimit)
            ->count();

        $nizkyStav = LekarnickeMaterial::query()
            ->whereIn('lekarnicky_id', $lekarnickyIds)
            ->whereColumn('aktualni_pocet', '<', 'minimalni_pocet')
            ->count();

        // 3) Kontroly - lékárničky které potřebují kontrolu
        $potrebaKontroly = $lekarnicke
            ->filter(fn($l) => $l->dalsi_kontrola && $l->dalsi_kontrola <= $kontrolaLimit)
            ->count();

        // 4) Úrazy tento měsíc
        $urazyMesic = Uraz::whereIn('lekarnicky_id', $lekarnickyIds)
            ->whereYear('datum_cas_urazu', $now->year)
            ->whereMonth('datum_cas_urazu', $now->month)
            ->count();

        return response()->json([
            'lekarnicke' => $lekarnicke,
            'statistiky' => [
                'celkem_lekarnicek'   => $lekarnicke->count(),
                'aktivni_lekarnicke'  => $lekarnicke->where('status', 'aktivni')->count(),
                'expirujici_material' => $expirujici,
                'nizky_stav_material' => $nizkyStav,
                'potreba_kontroly'    => $potrebaKontroly,
                'urazy_tento_mesic'   => $urazyMesic,
            ],
        ]);
    }

    /**
     * Data pro grafy v analytickém dashboardu
     */
    public function stats()
    {
        // 1. Trendy úrazů za posledních 6 měsíců
        $injuriesTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Uraz::whereYear('datum_cas_urazu', $date->year)
                         ->whereMonth('datum_cas_urazu', $date->month)
                         ->count();
            
            $injuriesTrend[] = [
                'label' => $date->translatedFormat('F'),
                'count' => $count
            ];
        }

        // 2. Stav materiálu (OK vs Nízký vs Expirovaný)
        $lekarnickyIds = $this->scopedLekarnickyQuery()->pluck('id');
        $materials = LekarnickeMaterial::whereIn('lekarnicky_id', $lekarnickyIds)->get();
        $materialStats = [
            'ok' => 0,
            'low' => 0,
            'expired' => 0
        ];

        foreach ($materials as $m) {
            if ($m->datum_expirace && Carbon::parse($m->datum_expirace)->isPast()) {
                $materialStats['expired']++;
            } elseif ($m->aktualni_pocet < $m->minimalni_pocet) {
                $materialStats['low']++;
            } else {
                $materialStats['ok']++;
            }
        }

        // 3. Status lékárniček (Kontroly)
        $lekarnicky = $this->scopedLekarnickyQuery()->get();
        $inspectionStats = [
            'done' => $lekarnicky->where('je_potreba_kontrola', false)->count(),
            'pending' => $lekarnicky->where('je_potreba_kontrola', true)->count()
        ];

        return response()->json([
            'injuries' => $injuriesTrend,
            'materials' => $materialStats,
            'inspections' => $inspectionStats
        ]);
    }

        public function store(StoreLekarnickyRequest $request)
    {
        $validated = $request->validated();

        // Najdi vybraného uživatele a převed jeho ID na jméno do textového sloupce
        $owner = \App\Models\User::select('id', 'firstname', 'lastname')
            ->where('is_active', true)
            ->findOrFail($validated['zodpovedna_osoba_user_id']);

        $ownerName = trim(($owner->firstname ?? '') . ' ' . ($owner->lastname ?? ''));
        if ($ownerName === '') {
            $ownerName = 'ID ' . $owner->id;
        }

        // Vytvoř lékárničku v transakci - pokud selže přiřazení access,
        // nezůstane lékárnička bez vlastníka
        $lekarnicky = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $owner, $ownerName) {
            $lekarnicky = \App\Models\Lekarnicky::create([
                'nazev'            => $validated['nazev'],
                'umisteni'         => $validated['umisteni'],
                'zodpovedna_osoba' => $ownerName,                       // textový popis pro UI
                'popis'            => $validated['popis']          ?? null,
                'status'           => $validated['status']         ?? 'aktivni',
                'dalsi_kontrola'   => $validated['dalsi_kontrola'] ?? null,
            ]);

            // Přiřaď vlastníka přes user_lekarnicky_access (level=admin)
            // - to je to, co notifikace skutečně používají.
            $owner->lekarnickAccess()->syncWithoutDetaching([
                $lekarnicky->id => ['access_level' => 'admin'],
            ]);

            return $lekarnicky;
        });

        return response()->json([
            'success'    => true,
            'lekarnicky' => $lekarnicky,
            'message'    => 'Lékárnička byla úspěšně vytvořena',
        ]);
    }

        /**
     * Seznam uživatelů, kteří mohou být vlastníky lékárničky.
     *
     * Kandidát = aktivní uživatel s alespoň jedním z oprávnění:
     *   lekarnicke.create, lekarnicke.edit
     *   nebo super_admin role
     */
    public function getAvailableOwners(): \Illuminate\Http\JsonResponse
    {
        $users = \App\Models\User::query()
            ->select('id', 'firstname', 'lastname', 'email', 'username')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'super_admin');
                })
                ->orWhereHas('roles.permissions', function ($q) {
                    $q->whereIn('permissions.name', ['lekarnicke.view', 'lekarnicke.create', 'lekarnicke.edit']);
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->map(function ($user) {
                $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
                return [
                    'id'    => $user->id,
                    'label' => $name !== '' ? $name : $user->username,
                    'email' => $user->email,
                ];
            });

        return response()->json($users);
    }


    public function show($id)
    {
        $lekarnicky = $this->scopedLekarnickyQuery()
            ->with(['material', 'urazy.zamestnanec'])
            ->findOrFail($id);

        return response()->json($lekarnicky);
    }

    public function update(UpdateLekarnickyRequest $request, $id)
    {
        $lekarnicky = Lekarnicky::findOrFail($id);

        $lekarnicky->update($request->validated());

        return response()->json([
            'success' => true,
            'lekarnicky' => $lekarnicky,
            'message' => 'Lékárnička byla aktualizována'
        ]);
    }
    //pro pozice lékárniček v planu 
        public function updatePlanPosition(\Illuminate\Http\Request $request, $id)
    {
        $validated = $request->validate([
            'plan_x' => 'nullable|numeric|min:0|max:100',
            'plan_y' => 'nullable|numeric|min:0|max:100',
        ]);

        $lekarnicky = \App\Models\Lekarnicky::findOrFail($id);
        $lekarnicky->update([
            'plan_x' => $validated['plan_x'] ?? null,
            'plan_y' => $validated['plan_y'] ?? null,
        ]);

        return response()->json([
            'success'    => true,
            'lekarnicky' => $lekarnicky,
            'message'    => 'Pozice na plánu uložena',
        ]);
    }


    public function destroy($id)
    {
        $lekarnicky = Lekarnicky::findOrFail($id);
        $lekarnicky->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lékárnička byla smazána'
        ]);
    }

    // Správa materiálu
    public function storeMaterial(\Illuminate\Http\Request $request, $lekarnicky_id)
    {
        // FIX V-08: Explicit kontrola, že lékárnička existuje
        if (!Lekarnicky::where('id', $lekarnicky_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lékárnička nenalezena.',
            ], 404);
        }
        $validated = $request->validate([
            'nazev_materialu'   => 'required|string|max:255',
            'typ_materialu'     => 'required|string|max:255',
            'aktualni_pocet'    => 'required|integer|min:0',
            'minimalni_pocet'   => 'required|integer|min:0',
            'maximalni_pocet'   => 'required|integer|min:1|gte:minimalni_pocet',
            'jednotka'          => 'required|string|max:50',
            'datum_expirace'    => 'nullable|date|after:today',
            'cena_za_jednotku'  => 'nullable|numeric|min:0|max:999999',
            'dodavatel'         => 'nullable|string|max:255',
            'poznamky'          => 'nullable|string|max:5000',
        ], [
            'maximalni_pocet.gte'   => 'Maximální počet musí být >= minimální počet.',
            'datum_expirace.after'  => 'Datum expirace musí být v budoucnosti.',
        ]);

        $material = LekarnickeMaterial::create(array_merge(
            $validated,
            ['lekarnicky_id' => $lekarnicky_id]   // bezpečně - whitelisted z URL
        ));

        return response()->json([
            'success'  => true,
            'material' => $material,
            'message'  => 'Materiál byl přidán',
        ]);
    }

    public function updateMaterial(UpdateMaterialRequest $request, $material_id)
    {
        $material = LekarnickeMaterial::findOrFail($material_id);
        $this->abortUnlessCanAccessLekarnicky($material->lekarnicky_id, 'admin');
        $material->update($request->validated());

        return response()->json([
            'success' => true,
            'material' => $material,
            'message' => 'Materiál byl aktualizován'
        ]);
    }

    public function doplnitMaterial(\Illuminate\Http\Request $request, $material_id)
    {
        $validated = $request->validate([
            'objednavka_id'   => 'nullable|exists:lekarnicky_material_objednavky,id',
            'mnozstvi'        => 'required_without:objednavka_id|nullable|integer|min:1',
            'datum_expirace'  => 'nullable|date|after:today',
            'poznamky'        => 'nullable|string|max:5000',
        ], [
            'mnozstvi.required_without' => 'Množství je povinné.',
            'mnozstvi.min'              => 'Množství musí být alespoň 1.',
            'datum_expirace.after'      => 'Datum expirace musí být v budoucnosti.',
        ]);

        return DB::transaction(function () use ($validated, $material_id) {
            $material = LekarnickeMaterial::lockForUpdate()->findOrFail($material_id);
            $this->abortUnlessCanAccessLekarnicky($material->lekarnicky_id, 'view');

            $objednavka = null;
            $mnozstvi = (int) ($validated['mnozstvi'] ?? 0);

            if (!empty($validated['objednavka_id'])) {
                $objednavka = LekarnickyMaterialObjednavka::lockForUpdate()->findOrFail($validated['objednavka_id']);

                abort_unless((int) $objednavka->material_id === (int) $material->id, 422, 'Objednávka nepatří k vybranému materiálu.');
                abort_unless($objednavka->status === 'vydano', 422, 'Doplnit lze jen materiál, který nákupčí označil jako předaný.');

                $mnozstvi = (int) $objednavka->mnozstvi;
            } else {
                $this->authorizeLekarnickyMaterialAdmin();
            }

            $material->increment('aktualni_pocet', $mnozstvi);

            $updates = [];
            if (!empty($validated['datum_expirace'])) {
                $updates['datum_expirace'] = $validated['datum_expirace'];
            }
            if (array_key_exists('poznamky', $validated)) {
                $updates['poznamky'] = $validated['poznamky'];
            }
            if ($updates) {
                $material->update($updates);
            }

            if ($objednavka) {
                $objednavka->update(['status' => 'doplneno']);
            }

            return response()->json([
                'success' => true,
                'material' => $material->fresh(),
                'objednavka' => $objednavka?->fresh(['lekarnicky', 'material']),
                'message' => 'Materiál byl doplněn do původní lékárničky',
            ]);
        });
    }

    public function destroyMaterial($material_id)
    {
        $material = LekarnickeMaterial::findOrFail($material_id);
        $this->abortUnlessCanAccessLekarnicky($material->lekarnicky_id, 'admin');
        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Materiál byl smazán'
        ]);
    }
/**
 * Samostatný endpoint pod lekarnicke.urazy oprávněním,
 */
    public function getZamestnanci()
    {
        $zamestnanci = Zamestnanec::select('id', 'jmeno', 'prijmeni', 'stredisko')
            ->orderBy('prijmeni')
            ->orderBy('jmeno')
            ->get();

    return response()->json($zamestnanci);
    }
    // Záznamy úrazů
    public function storeUraz(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'zamestnanec_id'         => 'required|exists:zamestnanci,id',
            'lekarnicky_id'          => 'required|exists:lekarnicke,id',
            'datum_cas_urazu'        => 'required|date|before_or_equal:now',
            'popis_urazu'            => 'required|string|min:10|max:5000',
            'misto_urazu'            => 'required|string|max:255',
            'zavaznost'              => 'required|in:lehky,stredni,tezky',
            'poskytnute_osetreni'    => 'required|string|min:5|max:5000',
            'osoba_poskytujici_pomoc'=> 'required|string|max:255',
            'prevezen_do_nemocnice'  => 'sometimes|boolean',
            'poznamky'               => 'nullable|string|max:5000',
        ], [
            'datum_cas_urazu.before_or_equal' => 'Datum úrazu nemůže být v budoucnosti.',
            'popis_urazu.min'                 => 'Popis úrazu je příliš krátký (min. 10 znaků).',
        ]);

        $this->abortUnlessCanAccessLekarnicky((int) $validated['lekarnicky_id'], 'view');

        $uraz = \App\Models\Uraz::create($validated);

        return response()->json([
            'success' => true,
            'uraz'    => $uraz->load(['zamestnanec', 'lekarnicky']),
            'message' => 'Záznam o úrazu byl vytvořen',
        ]);
    }

    /**
     * Seznam všech úrazů pro záznamy úrazů.
     */
    public function getUrazy()
    {
        $urazy = Uraz::with(['zamestnanec:id,jmeno,prijmeni,stredisko', 'lekarnicky:id,nazev,umisteni'])
            ->whereIn('lekarnicky_id', $this->scopedLekarnickyQuery()->pluck('id'))
            ->orderBy('datum_cas_urazu', 'desc')
            ->get();

        return response()->json($urazy);
    }

    /**
     * Smazání záznamu úrazu.
     */
    public function destroyUraz($id)
    {
        $uraz = Uraz::findOrFail($id);
        $this->abortUnlessCanAccessLekarnicky($uraz->lekarnicky_id, 'view');
        $uraz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Záznam úrazu byl smazán'
        ]);
    }

    // Výdej materiálu
    public function vydejMaterial(VydejMaterialRequest $request)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Lock řádku materiálu - žádný jiný request neudělá souběžný update
            $material = LekarnickeMaterial::lockForUpdate()->findOrFail($validated['material_id']);
            $this->abortUnlessCanAccessLekarnicky($material->lekarnicky_id, 'view');

            if ($material->aktualni_pocet < $validated['vydane_mnozstvi']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nedostatek materiálu na skladě (k dispozici: ' . $material->aktualni_pocet . ').',
                ], 400);
            }

            $vydej = VydejMaterialu::create([
                'uraz_id'           => $validated['uraz_id'] ?? null,
                'material_id'       => $validated['material_id'],
                'vydane_mnozstvi'   => $validated['vydane_mnozstvi'],
                'jednotka'          => $material->jednotka,
                'datum_vydeje'      => now(),
                'osoba_vydavajici'  => $this->currentUserDisplayName(),
                'poznamky'          => $request->input('poznamky'),
            ]);

            $material->decrement('aktualni_pocet', $validated['vydane_mnozstvi']);
            $material->refresh();

            $objednavka = null;
            if ($request->boolean('objednat_po_vydeji')) {
                $objednavka = $this->createMaterialObjednavka(
                    $material,
                    'vydej',
                    (int) $validated['vydane_mnozstvi'],
                    'Automaticky vytvořeno po výdeji materiálu.',
                    true
                );
            }

            return response()->json([
                'success' => true,
                'vydej'   => $vydej->load(['uraz', 'material']),
                'objednavka' => $objednavka?->load(['lekarnicky', 'material']),
                'message' => $objednavka
                    ? 'Materiál byl vydán a doplnění přidáno do objednávek'
                    : 'Materiál byl vydán a stav aktualizován',
            ]);
        }); 
    }

    public function getMaterialObjednavky()
    {
        $objednavky = LekarnickyMaterialObjednavka::with([
                'lekarnicky:id,nazev,umisteni',
                'material:id,lekarnicky_id,nazev_materialu,aktualni_pocet,minimalni_pocet,maximalni_pocet,jednotka,datum_expirace',
            ])
            ->orderByRaw("CASE status WHEN 'cekajici' THEN 1 WHEN 'objednano' THEN 2 WHEN 'vydano' THEN 3 ELSE 4 END")
            ->latest('datum_objednani')
            ->whereIn('lekarnicky_id', $this->scopedLekarnickyQuery()->pluck('id'))
            ->get();

        return response()->json($objednavky);
    }

    public function objednatMaterial(Request $request)
    {
        $this->authorizeLekarnickyOrder();

        $validated = $request->validate([
            'material_id' => 'required|exists:lekarnicky_material,id',
            'mnozstvi'    => 'nullable|integer|min:1',
            'duvod'       => 'nullable|string|max:50',
            'poznamky'    => 'nullable|string|max:5000',
        ]);

        $material = LekarnickeMaterial::with('lekarnicky')->findOrFail($validated['material_id']);
        $this->abortUnlessCanAccessLekarnicky($material->lekarnicky_id, 'view');

        $objednavka = $this->createMaterialObjednavka(
            $material,
            $validated['duvod'] ?? 'manual',
            $validated['mnozstvi'] ?? $this->suggestOrderQuantity($material),
            $validated['poznamky'] ?? null
        );

        return response()->json([
            'success' => true,
            'objednavka' => $objednavka->load(['lekarnicky', 'material']),
            'message' => $objednavka->wasRecentlyCreated
                ? 'Položka byla přidána do objednávek'
                : 'Položka už je v aktivních objednávkách',
        ]);
    }

    public function objednatExpirujiciMaterial($lekarnicky_id)
    {
        $this->authorizeLekarnickyOrder();

        $this->abortUnlessCanAccessLekarnicky($lekarnicky_id, 'view');

        $lekarnicky = Lekarnicky::with('material')->findOrFail($lekarnicky_id);
        $limit = now()->addDays(config('lekarnicke.material_expiration_warn_days', 30));
        $created = 0;
        $skipped = 0;

        foreach ($lekarnicky->material as $material) {
            if (!$material->datum_expirace || $material->datum_expirace->gt($limit)) {
                continue;
            }

            $reason = $material->datum_expirace->isPast() ? 'expirovano' : 'expirace';
            $objednavka = $this->createMaterialObjednavka(
                $material,
                $reason,
                $this->suggestOrderQuantity($material, true),
                'Automaticky přidáno z přehledu expirujících položek.'
            );

            $objednavka->wasRecentlyCreated ? $created++ : $skipped++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => $created > 0
                ? "Do objednávek přidáno položek: {$created}"
                : 'Všechny expirující položky už jsou v aktivních objednávkách',
        ]);
    }

    public function updateMaterialObjednavkaStatus(Request $request, $id)
    {
        $this->authorizeLekarnickyMaterialAdmin();

        $validated = $request->validate([
            'status' => 'required|in:cekajici,objednano,vydano',
        ]);

        $objednavka = LekarnickyMaterialObjednavka::findOrFail($id);
        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'objednano') {
            $updates['datum_objednano'] = now();
        }
        if ($validated['status'] === 'vydano') {
            $updates['datum_vydano'] = now();
        }

        $objednavka->update($updates);

        return response()->json([
            'success' => true,
            'objednavka' => $objednavka->fresh(['lekarnicky', 'material']),
            'message' => 'Stav objednávky byl aktualizován',
        ]);
    }

    public function destroyMaterialObjednavka($id)
    {
        $this->authorizeLekarnickyMaterialAdmin();

        $objednavka = LekarnickyMaterialObjednavka::findOrFail($id);
        $objednavka->delete();

        return response()->json([
            'success' => true,
            'message' => 'Objednávka byla smazána',
        ]);
    }

    private function createMaterialObjednavka(LekarnickeMaterial $material, string $duvod, int $mnozstvi, ?string $poznamky = null, bool $mergeExistingQuantity = false): LekarnickyMaterialObjednavka
    {
        $existing = LekarnickyMaterialObjednavka::where('material_id', $material->id)
            ->whereIn('status', ['cekajici', 'objednano'])
            ->first();

        if ($existing) {
            if ($mergeExistingQuantity) {
                $existing->increment('mnozstvi', max(1, $mnozstvi));
            }

            return $existing->fresh();
        }

        return LekarnickyMaterialObjednavka::create([
            'lekarnicky_id' => $material->lekarnicky_id,
            'material_id' => $material->id,
            'objednal_user_id' => session('user.id'),
            'nazev_materialu' => $material->nazev_materialu,
            'typ_materialu' => $material->typ_materialu,
            'jednotka' => $material->jednotka,
            'mnozstvi' => max(1, $mnozstvi),
            'duvod' => $duvod,
            'status' => 'cekajici',
            'datum_objednani' => now(),
            'poznamky' => $poznamky,
        ]);
    }

    private function suggestOrderQuantity(LekarnickeMaterial $material, bool $replaceExpiringStock = false): int
    {
        if ($replaceExpiringStock && (int) $material->aktualni_pocet > 0) {
            return (int) $material->aktualni_pocet;
        }

        $toMax = (int) $material->maximalni_pocet - (int) $material->aktualni_pocet;
        if ($toMax > 0) {
            return $toMax;
        }

        return max(1, (int) $material->minimalni_pocet);
    }

    private function authorizeLekarnickyOrder(): void
    {
        $perms = session('user.permissions', []);
        $allowed = session('user.is_super_admin')
            || in_array('lekarnicke.material', $perms)
            || in_array('lekarnicke.urazy', $perms);

        abort_unless($allowed, 403, 'Nemáte oprávnění objednávat materiál.');
    }

    private function authorizeLekarnickyMaterialAdmin(): void
    {
        $allowed = $this->canManageMaterialGlobally();

        abort_unless($allowed, 403, 'Nemáte oprávnění spravovat objednávky materiálu.');
    }

    private function canManageMaterialGlobally(): bool
    {
        $perms = session('user.permissions', []);

        return $this->hasGlobalLekarnickyAccess()
            && (
                session('user.is_super_admin')
                || in_array('lekarnicke.material', $perms, true)
                || in_array('lekarnicke.create', $perms, true)
                || in_array('lekarnicke.edit', $perms, true)
            );
    }

    private function scopedLekarnickyQuery()
    {
        $query = Lekarnicky::query();

        if ($this->hasGlobalLekarnickyAccess()) {
            return $query;
        }

        $userId = session('user.id') ?? session('user')['id'] ?? null;
        if (!$userId) {
            return $query->whereRaw('1 = 0');
        }

        $ids = DB::table('user_lekarnicky_access')
            ->where('user_id', $userId)
            ->pluck('lekarnicky_id');

        return $query->whereIn('id', $ids);
    }

    private function hasGlobalLekarnickyAccess(): bool
    {
        if (session('user.is_super_admin')) {
            return true;
        }

        $perms = session('user.permissions', []);
        if (array_intersect(['lekarnicke.create', 'lekarnicke.edit', 'lekarnicke.delete'], $perms)) {
            return true;
        }

        $userId = session('user.id') ?? session('user')['id'] ?? null;
        $hasAssignedLekarnicky = $userId
            ? DB::table('user_lekarnicky_access')->where('user_id', $userId)->exists()
            : false;

        return in_array('lekarnicke.material', $perms, true) && !$hasAssignedLekarnicky;
    }

    private function abortUnlessCanAccessLekarnicky(int $lekarnickyId, string $level = 'view'): void
    {
        if ($this->hasGlobalLekarnickyAccess()) {
            return;
        }

        $user = \App\Models\User::find(session('user.id') ?? session('user')['id'] ?? null);
        abort_unless($user && $user->canAccessLekarnicky($lekarnickyId, $level), 403, 'Nemáte přístup k této lékárničce.');
    }

    private function currentUserDisplayName(): string
    {
        $user = session('user', []);
        $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

        return $name !== ''
            ? $name
            : ($user['username'] ?? $user['email'] ?? 'Přihlášený uživatel');
    }

    // Kontrola lékárničky
    public function kontrola($id)
    {
        $lekarnicky = Lekarnicky::findOrFail($id);
        $lekarnicky->update([
            'posledni_kontrola' => now(),
            'dalsi_kontrola' => now()->addMonths(3)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kontrola byla zaznamenána'
        ]);
    }

    // Export/výkazy
    public function exportVykaz(Request $request)
    {
        $od = $request->get('od', now()->startOfMonth());
        $do = $request->get('do', now()->endOfMonth());

        $data = [
            'urazy' => Uraz::with(['zamestnanec', 'lekarnicky', 'vydejMaterialu.material'])
                ->whereBetween('datum_cas_urazu', [$od, $do])
                ->get(),
            'vydeje' => VydejMaterialu::with(['uraz.zamestnanec', 'material.lekarnicky'])
                ->whereBetween('datum_vydeje', [$od, $do])
                ->get(),
            'period' => ['od' => $od, 'do' => $do]
        ];

        return response()->json($data);
    }
}
