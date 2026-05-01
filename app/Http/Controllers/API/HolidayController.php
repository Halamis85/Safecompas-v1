<?php
// app/Http/Controllers/API/HolidayController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
   
    private const HOLIDAY_CACHE_TTL = 86400; // 24 h

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:1900|max:2100',
            'country_code' => 'nullable|string|size:2',
            'type' => 'nullable|string',
        ]);

        $year = $request->get('year', now()->year);
        $countryCode = strtoupper($request->get('country_code', 'CZ'));
        $type = $request->get('type');

        // Cache klíč obsahuje všechny dotazové parametry
        $cacheKey = "holidays:{$countryCode}:{$year}" . ($type ? ":{$type}" : '');

        try {
            // Cache::remember atomicky vrátí cached hodnotu nebo zavolá callback
            $formattedHolidays = Cache::remember(
                $cacheKey,
                self::HOLIDAY_CACHE_TTL,
                function () use ($year, $countryCode, $type) {
                    $holidays = Holiday::getForYear($year, $countryCode);

                    if ($type) {
                        $holidays = $holidays->where('type', $type);
                    }

                    if ($holidays->isEmpty()) {
                        // Vracíme null jako signál, že nic nebylo nalezeno —
                        // pak v hlavním kódu vrátíme 404
                        return null;
                    }

                    return $holidays->map(function ($holiday) {
                        return [
                            'id' => $holiday->id,
                            'name' => $holiday->name,
                            'date' => $holiday->calculated_date->format('Y-m-d'),
                            'country_code' => $holiday->country_code,
                            'type' => $holiday->type,
                            'is_public_holiday' => $holiday->is_public_holiday,
                            'notes' => $holiday->notes,
                            'is_dynamic' => $holiday->is_dynamic,
                        ];
                    })->values()->toArray();
                }
            );

            if ($formattedHolidays === null) {
                return response()->json(
                    ['message' => 'Nebyly nalezeny žádné svátky pro zadaný rok a zemi.'],
                    404
                );
            }

            return response()->json($formattedHolidays)
                ->header('Cache-Control', 'public, max-age=3600'); // 1h browser cache

        } catch (\Exception $e) {
            Log::error('Database Error in HolidayController: ' . $e->getMessage());

            return response()->json(
                ['error' => 'Interní chyba serveru při načítání svátků.'],
                500
            );
        }
    }

    public function today(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->get('country_code', 'CZ'));

        // Klíč obsahuje datum, takže každý den má vlastní cache
        $cacheKey = "holiday:today:{$countryCode}:" . now()->toDateString();

        $holiday = Cache::remember(
            $cacheKey,
            self::HOLIDAY_CACHE_TTL,
            fn() => Holiday::getToday($countryCode)
        );

        if ($holiday) {
            return response()->json([
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $holiday->calculated_date->format('Y-m-d'),
                'country_code' => $holiday->country_code,
                'type' => $holiday->type,
                'is_public_holiday' => $holiday->is_public_holiday,
                'notes' => $holiday->notes,
                'is_dynamic' => $holiday->is_dynamic,
            ])->header('Cache-Control', 'public, max-age=3600');
        }

        return response()->json(
            ['message' => 'Dnes není žádný svátek.'],
            404
        );
    }
}
