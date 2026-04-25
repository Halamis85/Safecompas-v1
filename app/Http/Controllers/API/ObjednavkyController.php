<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObjednavkaRequest;
use App\Models\Objednavka;
use App\Models\User;
use App\Notifications\OrderCreated as OrderCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ObjednavkyController extends Controller
{
    /**
     * Maximální délka base64 podpisu (znaků).
     * 500 000 znaků ≈ 375 KB binárních dat — pro podpis tužkou 100× víc než třeba.
     */
    private const MAX_SIGNATURE_LENGTH = 500000;

    /**
     * Maximální rozměry obrázku podpisu (px).
     */
    private const MAX_SIGNATURE_WIDTH = 2000;
    private const MAX_SIGNATURE_HEIGHT = 2000;

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
            $objednavka = Objednavka::create([
                'zamestnanec_id'  => $request->zamestnanec_id,
                'produkt_id'      => $request->produkt_id,
                'velikost'        => $request->velikost,
                'datum_objednani' => now()->toDateString(),
                'status'          => 'cekajici',
            ]);

            $objednavka->load(['zamestnanec', 'produkt']);

            // Notifikace adminům
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

            $zamestnanec = $objednavka->zamestnanec;
            $produkt = $objednavka->produkt;
            Log::info("Vytvořena objednávka ID: {$objednavka->id} pro: {$zamestnanec->full_name}, produkt: {$produkt->nazev}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Objednávka byla úspěšně odeslána.',
            ]);

        } catch (\Exception $e) {
            Log::error('Store order error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Chyba při vytváření objednávky',
            ], 500);
        }
    }

    public function objednat(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:objednavky,id',
        ]);

        $updated = DB::table('objednavky')
            ->where('id', $request->order_id)
            ->update(['status' => 'Objednano']);

        if ($updated === 0) {
            return response()->json([
                'error' => 'Objednávka nebyla nalezena nebo již byla označena jako objednaná.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Objednávka byla označena k objednání',
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:objednavky,id',
        ]);

        $deleted = DB::table('objednavky')
            ->where('id', $request->order_id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'error' => 'Objednávka nebyla nalezena.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Objednávka byla úspěšně odstraněna.',
        ]);
    }

    /**
     * Vydání objednávky s podpisem. Implementuje vícevrstvou ochranu proti DoS:
     * 1. Limit délky base64 stringu (Laravel validátor)
     * 2. Limit binární velikosti po dekódování
     * 3. Validace MIME hlavičky regulárním výrazem
     * 4. Validace rozměrů obrázku přes getimagesizefromstring (bez alokace pixel bufferu)
     * 5. Až nakonec imagecreatefromstring pro plné ověření
     */
    public function vydat(Request $request): JsonResponse
    {
        $request->validate([
            'order_id'  => 'required|exists:objednavky,id',
            'signature' => 'required|string|max:' . self::MAX_SIGNATURE_LENGTH,
        ], [
            'signature.max' => 'Podpis je příliš velký. Zkuste menší obrázek.',
        ]);

        $signature = $request->signature;

        // 1. Validace MIME hlavičky data URL
        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $signature)) {
            return response()->json(['error' => 'Neplatný formát podpisu.'], 400);
        }

        // 2. Extrakce base64 obsahu
        $parts = explode(',', $signature, 2);
        if (count($parts) !== 2) {
            return response()->json(['error' => 'Neplatný formát podpisu.'], 400);
        }

        $encodedData = str_replace(' ', '+', $parts[1]);
        $decoded = base64_decode($encodedData, true); // strict mode

        if ($decoded === false) {
            return response()->json(['error' => 'Neplatná data podpisu (base64).'], 400);
        }

        // 3. Validace binární velikosti po dekódování (~375 KB)
        $maxBinarySize = (int) (self::MAX_SIGNATURE_LENGTH * 0.75);
        if (strlen($decoded) > $maxBinarySize) {
            return response()->json(['error' => 'Podpis je příliš velký.'], 400);
        }

        // 4. Validace hlavičky obrázku BEZ alokace pixel bufferu
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false) {
            return response()->json(['error' => 'Neplatná data obrázku.'], 400);
        }

        [$width, $height] = $imageInfo;

        // 5. Validace rozměrů (ochrana proti decompression bomb)
        if ($width > self::MAX_SIGNATURE_WIDTH || $height > self::MAX_SIGNATURE_HEIGHT) {
            return response()->json([
                'error' => "Podpis je příliš velký. Maximální rozměry: " . self::MAX_SIGNATURE_WIDTH . "×" . self::MAX_SIGNATURE_HEIGHT . " px.",
            ], 400);
        }

        if ($width < 10 || $height < 10) {
            return response()->json(['error' => 'Podpis je příliš malý.'], 400);
        }

        // 6. Až teď bezpečné plné dekódování
        $imageResource = @imagecreatefromstring($decoded);
        if ($imageResource === false) {
            return response()->json(['error' => 'Neplatná data obrázku.'], 400);
        }
        imagedestroy($imageResource); // okamžitě uvolnit paměť

        // 7. Generování názvu souboru a uložení
        $date = now()->format('Ymd');
        $nextIndex = DB::table('objednavky')
                ->whereDate('datum_vydani', now())
                ->count() + 1;

        $filename = sprintf("podpis_%s_%03d.png", $date, $nextIndex);
        $storagePath = "signatures/{$filename}";

        // 8. Uložení souboru
        Storage::disk('public')->put($storagePath, $decoded);

        // 9. Aktualizace objednávky v transakci s rollbackem souboru při selhání
        DB::beginTransaction();
        try {
            $updated = DB::table('objednavky')
                ->where('id', $request->order_id)
                ->where('status', '!=', 'vydano')
                ->update([
                    'podpis_path'   => $filename,
                    'datum_vydani'  => now(),
                    'status'        => 'vydano',
                ]);

            if ($updated === 0) {
                DB::rollBack();
                // Cleanup: smažeme uložený soubor podpisu
                Storage::disk('public')->delete($storagePath);
                return response()->json(['error' => 'Objednávka nebyla nalezena nebo již byla vydána.'], 400);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Podpis úspěšně uložen',
                'path'    => $filename,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Cleanup: smažeme uložený soubor podpisu
            Storage::disk('public')->delete($storagePath);
            Log::error('Vydat order error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Chyba při ukládání podpisu.',
            ], 500);
        }
    }

    public function getLastInfo(Request $request): JsonResponse
    {
        $request->validate([
            'zamestnanec_id' => 'required|exists:zamestnanci,id',
            'produkt_id'     => 'required|exists:produkty,id',
        ]);

        $result = DB::table('objednavky')
            ->select('velikost', 'datum_vydani')
            ->where('zamestnanec_id', $request->zamestnanec_id)
            ->where('produkt_id', $request->produkt_id)
            ->orderBy('datum_vydani', 'desc')
            ->first();

        if ($result) {
            return response()->json([
                'success'       => true,
                'last_received' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Záznam nenalezen',
        ]);
    }
}
