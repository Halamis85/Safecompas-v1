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
        $lekarnicke = Lekarnicky::with(['material', 'urazy'])->get();

        $statistiky = [
            'celkem_lekarnicek' => $lekarnicke->count(),
            'aktivni_lekarnicke' => $lekarnicke->where('status', 'aktivni')->count(),
            'expirujici_material' => 0,
            'nizky_stav_material' => 0,
            'potreba_kontroly' => 0,
            'urazy_tento_mesic' => Uraz::whereMonth('datum_cas_urazu', now()->month)->count()
        ];

        foreach($lekarnicke as $lekarnicky) {
            $statistiky['expirujici_material'] += $lekarnicky->expirujici_material->count();
            $statistiky['nizky_stav_material'] += $lekarnicky->nizky_stav_material->count();
            if($lekarnicky->je_potreba_kontrola) {
                $statistiky['potreba_kontroly']++;
            }
        }

        return response()->json([
            'lekarnicke' => $lekarnicke,
            'statistiky' => $statistiky
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

    public function store(Request $request)
    {
        $request->validate([
            'nazev' => 'required|string|max:255',
            'umisteni' => 'required|string|max:255',
            'zodpovedna_osoba' => 'required|string|max:255',
            'popis' => 'nullable|string',
            'dalsi_kontrola' => 'nullable|date'
        ]);

        $lekarnicky = Lekarnicky::create($request->all());

        return response()->json([
            'success' => true,
            'lekarnicky' => $lekarnicky,
            'message' => 'Lékárnička byla úspěšně vytvořena'
        ]);
    }

    public function show($id)
    {
        $lekarnicky = Lekarnicky::with(['material', 'urazy.zamestnanec'])->findOrFail($id);

        return response()->json($lekarnicky);
    }

    public function update(Request $request, $id)
    {
        $lekarnicky = Lekarnicky::findOrFail($id);

        $request->validate([
            'nazev' => 'required|string|max:255',
            'umisteni' => 'required|string|max:255',
            'zodpovedna_osoba' => 'required|string|max:255'
        ]);

        $lekarnicky->update($request->all());

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
    public function storeMaterial(Request $request, $lekarnicky_id)
    {
        $request->validate([
            'nazev_materialu' => 'required|string|max:255',
            'typ_materialu' => 'required|string|max:255',
            'aktualni_pocet' => 'required|integer|min:0',
            'minimalni_pocet' => 'required|integer|min:0',
            'maximalni_pocet' => 'required|integer|min:1',
            'jednotka' => 'required|string|max:50',
            'datum_expirace' => 'nullable|date',
            'cena_za_jednotku' => 'nullable|numeric|min:0'
        ]);

        $material = LekarnickeMaterial::create([
            'lekarnicky_id' => $lekarnicky_id,
            ...$request->all()
        ]);

        return response()->json([
            'success' => true,
            'material' => $material,
            'message' => 'Materiál byl přidán'
        ]);
    }

    public function updateMaterial(Request $request, $material_id)
    {
        $material = LekarnickeMaterial::findOrFail($material_id);
        $material->update($request->all());

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
        $zamestnanci = \App\Models\Zamestnanec::select('id', 'jmeno', 'prijmeni', 'stredisko')
            ->orderBy('prijmeni')
            ->orderBy('jmeno')
            ->get();

    return response()->json($zamestnanci);
    }
    // Záznamy úrazů
    public function storeUraz(Request $request)
    {
        $request->validate([
            'zamestnanec_id' => 'required|exists:zamestnanci,id',
            'lekarnicky_id' => 'required|exists:lekarnicke,id',
            'datum_cas_urazu' => 'required|date',
            'popis_urazu' => 'required|string',
            'misto_urazu' => 'required|string|max:255',
            'zavaznost' => 'required|in:lehky,stredni,tezky',
            'poskytnute_osetreni' => 'required|string',
            'osoba_poskytujici_pomoc' => 'required|string|max:255'
        ]);

        $uraz = Uraz::create($request->all());

        return response()->json([
            'success' => true,
            'uraz' => $uraz->load(['zamestnanec', 'lekarnicky']),
            'message' => 'Záznam o úrazu byl vytvořen'
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
    public function vydejMaterial(Request $request)
    {
        $request->validate([
            'uraz_id' => 'required|exists:urazy,id',
            'material_id' => 'required|exists:lekarnicky_material,id',
            'vydane_mnozstvi' => 'required|integer|min:1',
            'osoba_vydavajici' => 'required|string|max:255'
        ]);

        $material = LekarnickeMaterial::findOrFail($request->material_id);

        // Kontrola dostupnosti
        if ($material->aktualni_pocet < $request->vydane_mnozstvi) {
            return response()->json([
                'success' => false,
                'message' => 'Nedostatek materiálu na skladě'
            ], 400);
        }

        // Vytvoření záznamu o výdeji
        $vydej = VydejMaterialu::create([
            'uraz_id' => $request->uraz_id,
            'material_id' => $request->material_id,
            'vydane_mnozstvi' => $request->vydane_mnozstvi,
            'jednotka' => $material->jednotka,
            'datum_vydeje' => now(),
            'osoba_vydavajici' => $request->osoba_vydavajici,
            'poznamky' => $request->poznamky
        ]);

        // Aktualizace stavu materiálu
        $material->aktualni_pocet -= $request->vydane_mnozstvi;
        $material->save();

        return response()->json([
            'success' => true,
            'vydej' => $vydej->load(['uraz', 'material']),
            'message' => 'Materiál byl vydán a stav aktualizován'
        ]);
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
