<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DruhOopp;
use App\Models\Produkt;
use Illuminate\Http\JsonResponse;


class ProduktyController extends Controller
{
    public function getDruhy(): JsonResponse
    {
        $druhy = DruhOopp::select('id', 'nazev')
            ->orderBy('nazev')
            ->get();

        return response()->json($druhy);
    }

    public function getProduktyByDruh($druhId): JsonResponse
    {
        $produkty = Produkt::select('id', 'nazev')
            ->where('druh_id', $druhId)
            ->orderBy('nazev')
            ->get();

        return response()->json($produkty);
    }

    public function show($produktId): JsonResponse
    {
        try {
            $produkt = Produkt::find($produktId);

            if (!$produkt) {
                return response()->json(['error' => 'Produkt nenalezen.'], 404);
            }

            // Debug - zkontroluj typ dat
            \Log::info('Produkt data:', [
                'id' => $produkt->id,
                'dostupne_velikosti' => $produkt->dostupne_velikosti,
                'type' => gettype($produkt->dostupne_velikosti)
            ]);

            // OPRAVA: Zkontroluj typ před explode()
            $dostupneVelikosti = [];

            if (is_string($produkt->dostupne_velikosti)) {
                // Pokud je to string, rozděl podle čárky
                $dostupneVelikosti = explode(',', $produkt->dostupne_velikosti);
            } elseif (is_array($produkt->dostupne_velikosti)) {
                // Pokud je to už array, použij přímo
                $dostupneVelikosti = $produkt->dostupne_velikosti;
            } elseif ($produkt->dostupne_velikosti) {
                // Pro jiné typy zkus převést na string
                $dostupneVelikosti = explode(',', (string)$produkt->dostupne_velikosti);
            }

            // Vyčisti prázdné hodnoty
            $dostupneVelikosti = array_filter(array_map('trim', $dostupneVelikosti));

            $response = [
                'id' => $produkt->id,
                'nazev' => $produkt->nazev,
                'obrazek' => $produkt->obrazek,
                'dostupne_velikosti' => array_values($dostupneVelikosti) // Resetuj indexy
            ];

            return response()->json($response);

        } catch (\Exception $e) {
           \Log::error('Chyba v ProduktyController@show: ' . $e->getMessage(), [
                'produkt_id' => $produktId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Interní chyba serveru',
                'message' => config('app.debug') ? $e->getMessage() : 'Kontaktujte administrátora'
            ], 500);
        }
    }
}
