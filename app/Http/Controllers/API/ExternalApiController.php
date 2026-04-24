<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ExternalApiController extends Controller
{

    public function weather(Request $request): JsonResponse
    {
        $city = $request->get('city', 'Liberec');
        $countryCode = $request->get('country_code', 'CZ');

        // Získej API klíč z configu
        $apiKey = config('services.openweather.api_key', env('OPENWEATHER_API_KEY'));

        if (!$apiKey) {
            return response()->json(['message' => 'API klíč pro počasí není nastaven'], 500);
        }

        try {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => "{$city},{$countryCode}",
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'cs'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Formátuj data pro frontend
                return response()->json([
                    'location' => $data['name'] ?? $city,
                    'temperature_celsius' => $data['main']['temp'] ?? null,
                    'weather_description' => $data['weather'][0]['description'] ?? '',
                    'humidity' => $data['main']['humidity'] ?? 0,
                    'wind_speed_mps' => $data['wind']['speed'] ?? 0,
                    'icon_url' => $data['weather'][0]['icon'] ?? null
                ]);
            }

            // Logování chyby z API
            \Log::error('OpenWeather API Error: ' . $response->body());

            return response()->json([
                'message' => 'Nepodařilo se načíst počasí z OpenWeather',
                'details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            \Log::error('Weather Controller Exception: ' . $e->getMessage());
            return response()->json(['message' => 'Chyba připojení k API počasí'], 500);
        }
    }
}
