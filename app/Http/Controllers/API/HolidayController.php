<?php
// app/Http/Controllers/API/HolidayController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:1900|max:2100',
            'country_code' => 'nullable|string|size:2',
            'type' => 'nullable|string'
        ]);

        $year = $request->get('year', now()->year);
        $countryCode = strtoupper($request->get('country_code', 'CZ'));
        $type = $request->get('type');

        try {
            $holidays = Holiday::getForYear($year, $countryCode);

            if ($type) {
                $holidays = $holidays->where('type', $type);
            }

            if ($holidays->isEmpty()) {
                return response()->json([
                    'message' => 'Nebyly nalezeny žádné svátky pro zadaný rok a zemi.'
                ], 404);
            }

            $formattedHolidays = $holidays->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->calculated_date->format('Y-m-d'),
                    'country_code' => $holiday->country_code,
                    'type' => $holiday->type,
                    'is_public_holiday' => $holiday->is_public_holiday,
                    'notes' => $holiday->notes,
                    'is_dynamic' => $holiday->is_dynamic
                ];
            })->values();

            return response()->json($formattedHolidays);

        } catch (\Exception $e) {
            \Log::error('Database Error in HolidayController: ' . $e->getMessage());

            return response()->json([
                'error' => 'Interní chyba serveru při načítání svátků.'
            ], 500);
        }
    }

    public function today(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->get('country_code', 'CZ'));

        $holiday = Holiday::getToday($countryCode);

        if ($holiday) {
            return response()->json([
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $holiday->calculated_date->format('Y-m-d'),
                'country_code' => $holiday->country_code,
                'type' => $holiday->type,
                'is_public_holiday' => $holiday->is_public_holiday,
                'notes' => $holiday->notes,
                'is_dynamic' => $holiday->is_dynamic
            ]);
        }

        return response()->json([
            'message' => 'Dnes není žádný svátek.'
        ], 404);
    }
}
