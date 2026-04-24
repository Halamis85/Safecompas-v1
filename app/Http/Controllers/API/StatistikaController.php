<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatistikaController extends Controller
{
    /**
     * Statistiky produktů za rok (pro pie chart)
     */
    public function data(Request $request): JsonResponse
    {
        $rok = $request->get('rok', now()->year);

        try {
            $statistiky = DB::table('objednavky')
                ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
                ->select('produkty.nazev as produkt', DB::raw('COUNT(objednavky.produkt_id) as pocet'))
                ->where('objednavky.status', 'vydano')
                ->whereYear('objednavky.datum_vydani', $rok)
                ->groupBy('produkty.nazev')
                ->orderBy('pocet', 'desc')
                ->get();

            return response()->json($statistiky);

        } catch (\Exception $e) {
            \Log::error('Chyba při načítání statistik: ' . $e->getMessage());

            return response()->json([
                'error' => 'Chyba při načítání statistik'
            ], 500);
        }
    }

    /**
     * Výdaje za rok podle měsíců (pro bar chart)
     */
    public function vydajeZaRok(Request $request): JsonResponse
    {
        $rok = $request->get('rok', now()->year);

        try {
            // Query pro získání výdajů podle měsíců
            $vydaje = DB::table('objednavky')
                ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
                ->select(
                    DB::raw('MONTH(objednavky.datum_vydani) as mesic_cislo'),
                    DB::raw('SUM(produkty.cena) as vydaje')
                )
                ->where('objednavky.status', 'vydano')
                ->whereYear('objednavky.datum_vydani', $rok)
                ->groupBy(DB::raw('MONTH(objednavky.datum_vydani)'))
                ->orderBy('mesic_cislo')
                ->get();

            // Přidáme názvy měsíců pro frontend
            $mesice = [
                1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
                5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
                9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
            ];

            $result = $vydaje->map(function ($item) use ($mesice) {
                return [
                    'mesic_cislo' => $item->mesic_cislo,
                    'mesic' => $mesice[$item->mesic_cislo] ?? 'Neznámý',
                    'vydaje' => (float) $item->vydaje
                ];
            });

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Chyba při načítání výdajů: ' . $e->getMessage());

            return response()->json([
                'error' => 'Chyba při načítání výdajů'
            ], 500);
        }
    }

    /**
     * Souhrnné statistiky pro dashboard
     */
    public function souhrn(Request $request): JsonResponse
    {
        $rok = $request->get('rok', now()->year);

        try {
            // Celkový počet objednávek
            $celkemObjednavek = DB::table('objednavky')
                ->whereYear('datum_objednani', $rok)
                ->count();

            // Vydané objednávky
            $vydaneObjednavky = DB::table('objednavky')
                ->where('status', 'vydano')
                ->whereYear('datum_vydani', $rok)
                ->count();

            // Čekající objednávky
            $cekajiciObjednavky = DB::table('objednavky')
                ->where('status', 'cekajici')
                ->count();

            // Celkové výdaje
            $celkoveVydaje = DB::table('objednavky')
                ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
                ->where('objednavky.status', 'vydano')
                ->whereYear('objednavky.datum_vydani', $rok)
                ->sum('produkty.cena');

            // Nejpopulárnější produkt
            $nejpopularnejsiProdukt = DB::table('objednavky')
                ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
                ->select('produkty.nazev', DB::raw('COUNT(*) as pocet'))
                ->where('objednavky.status', 'vydano')
                ->whereYear('objednavky.datum_vydani', $rok)
                ->groupBy('produkty.nazev')
                ->orderBy('pocet', 'desc')
                ->first();

            return response()->json([
                'rok' => $rok,
                'celkem_objednavek' => $celkemObjednavek,
                'vydane_objednavky' => $vydaneObjednavky,
                'cekajici_objednavky' => $cekajiciObjednavky,
                'celkove_vydaje' => (float) $celkoveVydaje,
                'nejpopularnejsi_produkt' => $nejpopularnejsiProdukt
            ]);

        } catch (\Exception $e) {
            \Log::error('Chyba při načítání souhrnných statistik: ' . $e->getMessage());

            return response()->json([
                'error' => 'Chyba při načítání souhrnných statistik'
            ], 500);
        }
    }

    /**
     * Statistiky podle středisek
     */
    public function podleStredisek(Request $request): JsonResponse
    {
        $rok = $request->get('rok', now()->year);

        try {
            $statistiky = DB::table('objednavky')
                ->join('zamestnanci', 'objednavky.zamestnanec_id', '=', 'zamestnanci.id')
                ->select('zamestnanci.stredisko', DB::raw('COUNT(*) as pocet'))
                ->where('objednavky.status', 'vydano')
                ->whereYear('objednavky.datum_vydani', $rok)
                ->groupBy('zamestnanci.stredisko')
                ->orderBy('pocet', 'desc')
                ->get();

            return response()->json($statistiky);

        } catch (\Exception $e) {
            \Log::error('Chyba při načítání statistik podle středisek: ' . $e->getMessage());

            return response()->json([
                'error' => 'Chyba při načítání statistik podle středisek'
            ], 500);
        }
    }

    /**
     * Trend objednávek podle měsíců
     */
    public function trendObjednavek(Request $request): JsonResponse
    {
        $rok = $request->get('rok', now()->year);

        try {
            $trend = DB::table('objednavky')
                ->select(
                    DB::raw('MONTH(datum_objednani) as mesic'),
                    DB::raw('COUNT(*) as pocet_objednavek'),
                    DB::raw('SUM(CASE WHEN status = "vydano" THEN 1 ELSE 0 END) as vydano'),
                    DB::raw('SUM(CASE WHEN status = "cekajici" THEN 1 ELSE 0 END) as cekajici')
                )
                ->whereYear('datum_objednani', $rok)
                ->groupBy(DB::raw('MONTH(datum_objednani)'))
                ->orderBy('mesic')
                ->get();

            // Přidáme názvy měsíců
            $mesice = [
                1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
                5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
                9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
            ];

            $result = $trend->map(function ($item) use ($mesice) {
                return [
                    'mesic_cislo' => $item->mesic,
                    'mesic' => $mesice[$item->mesic] ?? 'Neznámý',
                    'pocet_objednavek' => $item->pocet_objednavek,
                    'vydano' => $item->vydano,
                    'cekajici' => $item->cekajici
                ];
            });

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Chyba při načítání trendu objednávek: ' . $e->getMessage());

            return response()->json([
                'error' => 'Chyba při načítání trendu objednávek'
            ], 500);
        }
    }
}
