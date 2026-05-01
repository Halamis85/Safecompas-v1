<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalApiController extends Controller
{
    /**
     * Cache TTL pro počasí. 10 minut je rozumný kompromis — počasí se
     * nezmění tak rychle, ale uživatel uvidí čerstvá data.
     */
    private const WEATHER_CACHE_TTL = 600;

    /**
     * Pokud se OpenWeather API nepodaří kontaktovat (timeout, 503),
     * vrátíme poslední úspěšnou odpověď z této fallback cache, která má
     * delší TTL (24 hodin). To je tzv. "stale-while-revalidate" pattern —
     * uživatel uvidí staré počasí místo chybové hlášky.
     */
    private const WEATHER_FALLBACK_TTL = 86400;

    public function weather(Request $request): JsonResponse
    {
        $city = $request->get('city', 'Liberec');
        $countryCode = $request->get('country_code', 'CZ');

        // Cache klíč musí obsahovat všechny parametry, podle kterých se data liší
        $cacheKey = "weather:{$countryCode}:{$city}";
        $fallbackKey = "weather:fallback:{$countryCode}:{$city}";

        // === Cache hit — vrátíme okamžitě ===
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached)
                ->header('X-Cache', 'HIT')
                ->header('Cache-Control', 'public, max-age=' . self::WEATHER_CACHE_TTL);
        }

        // === Cache miss — voláme OpenWeather API ===
        $apiKey = config('services.openweather.api_key', env('OPENWEATHER_API_KEY'));

        if (!$apiKey) {
            return response()->json(
                ['message' => 'API klíč pro počasí není nastaven'],
                500
            );
        }

        try {
            $response = Http::timeout(10)->get(
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'q' => "{$city},{$countryCode}",
                    'appid' => $apiKey,
                    'units' => 'metric',
                    'lang' => 'cs',
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                $payload = [
                    'location' => $data['name'] ?? $city,
                    'temperature_celsius' => $data['main']['temp'] ?? null,
                    'weather_description' => $data['weather'][0]['description'] ?? '',
                    'humidity' => $data['main']['humidity'] ?? 0,
                    'wind_speed_mps' => $data['wind']['speed'] ?? 0,
                    'icon_url' => $data['weather'][0]['icon'] ?? null,
                ];

                // Uložíme do hlavní cache (10 min) i do fallback cache (24 h)
                Cache::put($cacheKey, $payload, self::WEATHER_CACHE_TTL);
                Cache::put($fallbackKey, $payload, self::WEATHER_FALLBACK_TTL);

                return response()->json($payload)
                    ->header('X-Cache', 'MISS')
                    ->header('Cache-Control', 'public, max-age=' . self::WEATHER_CACHE_TTL);
            }

            // OpenWeather API odpověděl, ale s chybou (4xx/5xx)
            Log::error('OpenWeather API Error: ' . $response->body());

            return $this->fallbackOrError($fallbackKey, [
                'message' => 'Nepodařilo se načíst počasí z OpenWeather',
                'details' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            // Síťová chyba, timeout, DNS issue
            Log::error('Weather Controller Exception: ' . $e->getMessage());

            return $this->fallbackOrError($fallbackKey, [
                'message' => 'Chyba připojení k API počasí',
            ], 500);
        }
    }

    /**
     * Pokud máme v fallback cache poslední úspěšnou odpověď, vrátíme ji.
     * Jinak vrátíme předanou chybu.
     */
    private function fallbackOrError(string $fallbackKey, array $errorPayload, int $errorStatus): JsonResponse
    {
        $fallback = Cache::get($fallbackKey);

        if ($fallback !== null) {
            return response()->json($fallback)
                ->header('X-Cache', 'STALE')
                ->header('Cache-Control', 'public, max-age=60');
        }

        return response()->json($errorPayload, $errorStatus);
    }
}
