<?php
// app/Http/Controllers/LekarnickController.php

namespace App\Http\Controllers;

use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
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
            'descriptions' => 'Správa a sledování lékárniček'
        ];
        return view('lekarnicky.index', $data);
    }
    public function dashboard()
    {
        // Místo iterace přes N lékárniček a volání accessoru pro každou
        // jdou všechny statistiky 4 agregačními dotazy.
        $now = now();
        $expirationLimit = $now->copy()->addDays(config('lekarnicke.material_expiration_warn_days', 30));
        $kontrolaLimit   = $now->copy()->addDays(config('lekarnicke.kontrola_warn_days', 7));

        // 1) Lékárničky - počet a aktivní
        $lekarnicke = Lekarnicky::select('id', 'status', 'dalsi_kontrola')->get();

        // 2) Materiál - dva dotazy nad celou tabulkou (cca 1 ms i pro 100k řádků s indexy)
        $expirujici = LekarnickeMaterial::query()
            ->whereNotNull('datum_expirace')
            ->where('datum_expirace', '>=', $now)
            ->where('datum_expirace', '<=', $expirationLimit)
            ->count();

        $nizkyStav = LekarnickeMaterial::query()
            ->whereColumn('aktualni_pocet', '<=', 'minimalni_pocet')
            ->count();

        // 3) Kontroly - lékárničky které potřebují kontrolu
        $potrebaKontroly = $lekarnicke
            ->filter(fn($l) => $l->dalsi_kontrola && $l->dalsi_kontrola <= $kontrolaLimit)
            ->count();

        // 4) Úrazy tento měsíc
        $urazyMesic = Uraz::whereYear('datum_cas_urazu', $now->year)
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
        $materials = LekarnickeMaterial::all();
        $materialStats = [
            'ok' => 0,
            'low' => 0,
            'expired' => 0
        ];

        foreach ($materials as $m) {
            if ($m->datum_expirace && Carbon::parse($m->datum_expirace)->isPast()) {
                $materialStats['expired']++;
            } elseif ($m->aktualni_pocet <= $m->minimalni_pocet) {
                $materialStats['low']++;
            } else {
                $materialStats['ok']++;
            }
        }

        // 3. Status lékárniček (Kontroly)
        $lekarnicky = Lekarnicky::all();
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
        $lekarnicky = \App\Models\Lekarnicky::create($request->validated());

        return response()->json([
            'success'    => true,
            'lekarnicky' => $lekarnicky,
            'message'    => 'Lékárnička byla úspěšně vytvořena',
        ]);
    }

    public function show($id)
    {
        $lekarnicky = Lekarnicky::with(['material', 'urazy.zamestnanec'])->findOrFail($id);

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
        $material->update($request->validated());

        return response()->json([
            'success' => true,
            'material' => $material,
            'message' => 'Materiál byl aktualizován'
        ]);
    }

    public function destroyMaterial($material_id)
    {
        $material = LekarnickeMaterial::findOrFail($material_id);
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

            if ($material->aktualni_pocet < $validated['vydane_mnozstvi']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nedostatek materiálu na skladě (k dispozici: ' . $material->aktualni_pocet . ').',
                ], 400);
            }

            $vydej = VydejMaterialu::create([
                'uraz_id'           => $validated['uraz_id'],
                'material_id'       => $validated['material_id'],
                'vydane_mnozstvi'   => $validated['vydane_mnozstvi'],
                'jednotka'          => $material->jednotka,
                'datum_vydeje'      => now(),
                'osoba_vydavajici'  => $validated['osoba_vydavajici'],
                'poznamky'          => $request->input('poznamky'),
            ]);

            $material->decrement('aktualni_pocet', $validated['vydane_mnozstvi']);

            return response()->json([
                'success' => true,
                'vydej'   => $vydej->load(['uraz', 'material']),
                'message' => 'Materiál byl vydán a stav aktualizován',
            ]);
        }); 
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
