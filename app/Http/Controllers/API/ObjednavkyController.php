<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObjednavkaRequest;
use App\Models\Objednavka;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Notifications\OrderCreated as OrderCreatedNotification;
use App\Events\OrderCreated as OrderCreatedEvent;
use App\Models\User;

class ObjednavkyController extends Controller
{
    public function getAktivni(): JsonResponse
    {
        $objednavky = DB::table('objednavky')
            ->join('zamestnanci', 'objednavky.zamestnanec_id', '=', 'zamestnanci.id')
            ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
            ->select(
                'objednavky.id',
                'zamestnanci.jmeno',
                'zamestnanci.prijmeni',
                'produkty.nazev as produkt',
                'produkty.obrazek',
                'objednavky.velikost',
                'objednavky.status',
                'objednavky.datum_objednani'
            )
            ->whereIn('objednavky.status', ['cekajici', 'Objednano'])
            ->orderBy('objednavky.datum_objednani', 'desc')
            ->get();

        return response()->json($objednavky);
    }

    public function store(StoreObjednavkaRequest $request): JsonResponse
    {
        try {

        $userId = session('user.id');

        $objednavka = Objednavka::create([
            'zamestnanec_id' => $request->zamestnanec_id,
            'produkt_id' => $request->produkt_id,
            'velikost' => $request->velikost,
            'datum_objednani' => now()->toDateString(),
            'status' => 'cekajici'
        ]);
        // Načti objednávku s relacemi
        $objednavka->load(['zamestnanec', 'produkt']);

        // Pošli notifikaci všem adminům
        $admins = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })
            ->where('is_active', true)
            ->get();

        /** @var \App\Models\User $admin */    
        foreach ($admins as $admin) {
            try {
                $admin->notify(new OrderCreatedNotification($objednavka));
            } catch (\Exception $e) {
                Log::warning("Notifikace se nepodařilo poslat uživateli {$admin->id}: " . $e->getMessage());
            }
        }

            // Logování
        $zamestnanec = $objednavka->zamestnanec;
        $produkt = $objednavka->produkt;

        $activity = "Vytvořena objednávka ID: {$objednavka->id} pro: {$zamestnanec->full_name}, produkt: {$produkt->nazev}";
        Log::info($activity);

        return response()->json([
            'status' => 'success',
            'message' => 'Objednávka byla úspěšně odeslána.'
        ]);

    } catch (\Exception $e) {
    \Log::error('Store order error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Chyba při vytváření objednávky'
            ], 500);
        }
    }

    public function objednat(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:objednavky,id'
        ]);

        $updated = DB::table('objednavky')
            ->where('id', $request->order_id)
            ->update(['status' => 'Objednano']);

        if ($updated === 0) {
            return response()->json([
                'error' => 'Objednávka nebyla nalezena nebo již byla označena jako objednaná.'
            ], 404);
        }

        return response()->json([
            'success' => 'success',
            'message' => 'Objednávka byla označena k objednání'
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:objednavky,id'
        ]);

        $deleted = DB::table('objednavky')
            ->where('id', $request->order_id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'error' => 'Objednávka nebyla nalezena.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Objednávka byla úspěšně odstraněna.'
        ]);
    }

    public function vydat(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:objednavky,id',
            'signature' => 'required|string'
        ]);

        $signature = $request->signature;

        // Validace formátu podpisu
        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $signature)) {
            return response()->json(['error' => 'Neplatný formát podpisu.'], 400);
        }

        [$header, $content] = explode(';', $signature);
        [, $encodedData] = explode(',', $content);
        $decoded = base64_decode(str_replace(' ', '+', $encodedData));

        // Validace obrázku
        if (!@imagecreatefromstring($decoded)) {
            return response()->json(['error' => 'Neplatná data obrázku.'], 400);
        }

        // Generování názvu souboru
        $date = now()->format('Ymd');
        $nextIndex = DB::table('objednavky')
                ->whereDate('datum_vydani', now())
                ->count() + 1;

        $filename = sprintf("podpis_%s_%03d.png", $date, $nextIndex);

        // Uložení do storage
        Storage::disk('public')->put("signatures/{$filename}", $decoded);

        // Aktualizace objednávky
        DB::beginTransaction();
        try {
            $updated = DB::table('objednavky')
                ->where('id', $request->order_id)
                ->where('status', '!=', 'vydano')
                ->update([
                    'podpis_path' => $filename,
                    'datum_vydani' => now(),
                    'status' => 'vydano'
                ]);

            if ($updated === 0) {
                DB::rollBack();
                return response()->json(['error' => 'Objednávka nebyla nalezena nebo již byla vydána.'], 400);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Podpis úspěšně uložen',
                'path' => $filename
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getLastInfo(Request $request): JsonResponse
    {
        $request->validate([
            'zamestnanec_id' => 'required|exists:zamestnanci,id',
            'produkt_id' => 'required|exists:produkty,id'
        ]);

        $result = DB::table('objednavky')
            ->select('velikost', 'datum_vydani')
            ->where('zamestnanec_id', $request->zamestnanec_id)
            ->where('produkt_id', $request->produkt_id)
            ->orderBy('datum_vydani', 'desc')
            ->first();

        if ($result) {
            return response()->json([
                'success' => true,
                'last_received' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Záznam nenalezen'
        ]);
    }
}
