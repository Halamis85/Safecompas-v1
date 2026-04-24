<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Zamestnanec;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ZamestnanciController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = Zamestnanec::query();

        if ($request->has('q')) {
            $search = $request->get('q');
            $query->where(function($q) use ($search) {
                $q->where('jmeno', 'LIKE', "%{$search}%")
                    ->orWhere('prijmeni', 'LIKE', "%{$search}%");
            });
        }

        $zamestnanci = $query->select('id', 'jmeno', 'prijmeni', 'stredisko')
            ->limit(10)
            ->get();

        return response()->json($zamestnanci);
    }

    public function index(): JsonResponse
    {
        $employees = Zamestnanec::select('id', 'jmeno', 'prijmeni', 'stredisko')->get();
        return response()->json($employees);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'jmeno' => 'required|string|max:255',
            'prijmeni' => 'required|string|max:255',
            'stredisko' => 'required|string|max:255'
        ]);

        $zamestnanec = Zamestnanec::create($request->only(['jmeno', 'prijmeni', 'stredisko']));

        return response()->json([
            'status' => 'success',
            'message' => 'Zaměstnanec byl úspěšně přidán.'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $zamestnanec = Zamestnanec::find($id);

        if (!$zamestnanec) {
            return response()->json([
                'error' => "Zaměstnanec s ID {$id} nebyl nalezen nebo již byl smazán."
            ], 404);
        }

        $zamestnanec->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Zaměstnanec s ID {$id} byl úspěšně odebrán."
        ]);
    }

    public function getObjednavkyVydane($zamestnanecId): JsonResponse
    {
        if (!is_numeric($zamestnanecId) || (int)$zamestnanecId <= 0) {
            return response()->json(['error' => 'Neplatné ID zaměstnance.'], 400);
        }

        $orders = DB::table('objednavky')
            ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
            ->select('produkty.nazev as produkt', 'objednavky.velikost', 'objednavky.datum_vydani', 'objednavky.podpis_path')
            ->where('objednavky.zamestnanec_id', $zamestnanecId)
            ->where('objednavky.status', 'vydano')
            ->orderBy('objednavky.datum_vydani', 'desc')
            ->get()
            ->map(function ($order) {
                if ($order->datum_vydani) {
                    $dateTime = new \DateTime($order->datum_vydani);
                    $order->datum_vydani = $dateTime->format("j n Y");
                }
                return $order;
            });

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
}
