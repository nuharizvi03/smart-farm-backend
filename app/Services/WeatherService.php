<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WeatherService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected int $cacheMinutes;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key');

        $this->baseUrl = rtrim(
            config(
                'services.openweather.base_url',
                'https://api.openweathermap.org'
            ),
            '/'
        );

        $this->cacheMinutes = (int) config(
            'services.openweather.cache_minutes',
            30
        );
    }

    /**
     * Get weather data for a district.
     *
     * Includes:
     * - Location
     * - Current weather
     * - Daily forecast
     * - Adverse weather alerts
     * - Cache information
     * - API fallback information
     */
    public function getWeatherByDistrict(
        string $district,
        ?string $countryCode = 'LK'
    ): array {
        $location = $this->getCoordinates(
            $district,
            $countryCode
        );

        return $this->getWeatherByCoordinates(
            (float) $location['lat'],
            (float) $location['lon'],
            $district,
            $countryCode
        );
    }

    /**
     * Get coordinates using OpenWeather Geocoding API.
     */
    public function getCoordinates(
        string $district,
        ?string $countryCode = 'LK'
    ): array {
        $query = trim($district);

        if ($countryCode) {
            $query .= ',' . strtoupper($countryCode);
        }

        $cacheKey = 'weather:geocoding:' .
            Str::slug(strtolower($query));

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($this->cacheMinutes),
            function () use ($query) {

                $response = Http::timeout(10)
                    ->retry(2, 500)
                    ->get(
                        $this->baseUrl . '/geo/1.0/direct',
                        [
                            'q' => $query,
                            'limit' => 1,
                            'appid' => $this->apiKey,
                        ]
                    );

                if (!$response->successful()) {
                    throw new RuntimeException(
                        'Unable to retrieve location coordinates.'
                    );
                }

                $locations = $response->json();

                if (
                    !is_array($locations) ||
                    empty($locations)
                ) {
                    throw new RuntimeException(
                        'District location was not found.'
                    );
                }

                return [
                    'name' =>
                        $locations[0]['name'] ?? null,

                    'lat' =>
                        $locations[0]['lat'] ?? null,

                    'lon' =>
                        $locations[0]['lon'] ?? null,

                    'country' =>
                        $locations[0]['country'] ?? null,

                    'state' =>
                        $locations[0]['state'] ?? null,
                ];
            }
        );
    }

    /**
     * Get current weather and forecast using coordinates.
     */
    public function getWeatherByCoordinates(
        float $latitude,
        float $longitude,
        ?string $district = null,
        ?string $countryCode = 'LK'
    ): array {

        $locationKey =
            round($latitude, 4) .
            ':' .
            round($longitude, 4);

        $hash = md5($locationKey);

        $freshCacheKey =
            'weather:fresh:' . $hash;

        $lastCacheKey =
            'weather:last:' . $hash;

        /*
        |--------------------------------------------------------------------------
        | Fresh Cache
        |--------------------------------------------------------------------------
        */

        $cachedWeather = Cache::get($freshCacheKey);

        if ($cachedWeather) {
            return array_merge(
                $cachedWeather,
                [
                    'source' => 'cache',
                    'fallback' => false,
                ]
            );
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Current Weather API
            |--------------------------------------------------------------------------
            */

            $currentResponse = Http::timeout(10)
                ->retry(2, 500)
                ->get(
                    $this->baseUrl . '/data/2.5/weather',
                    [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'units' => 'metric',
                        'appid' => $this->apiKey,
                    ]
                );

            if (!$currentResponse->successful()) {
                throw new RuntimeException(
                    'Unable to retrieve current weather data.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Forecast API
            |--------------------------------------------------------------------------
            */

            $forecastResponse = Http::timeout(10)
                ->retry(2, 500)
                ->get(
                    $this->baseUrl . '/data/2.5/forecast',
                    [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'units' => 'metric',
                        'appid' => $this->apiKey,
                    ]
                );

            if (!$forecastResponse->successful()) {
                throw new RuntimeException(
                    'Unable to retrieve forecast data.'
                );
            }

            $currentWeather = $currentResponse->json();

            $forecastData = $forecastResponse->json();

            $forecastList =
                $forecastData['list'] ?? [];

            /*
            |--------------------------------------------------------------------------
            | Build Daily Forecast
            |--------------------------------------------------------------------------
            */

            $dailyForecast = $this->buildDailyForecast(
                $forecastList
            );

            /*
            |--------------------------------------------------------------------------
            | Build Weather Alerts
            |--------------------------------------------------------------------------
            */

            $alerts = $this->buildWeatherAlerts(
                $currentWeather,
                $forecastList
            );

            /*
            |--------------------------------------------------------------------------
            | Final Weather Data
            |--------------------------------------------------------------------------
            */

            $data = [

                'location' => [
                    'district' =>
                        $district,

                    'country_code' =>
                        $countryCode,

                    'latitude' =>
                        $latitude,

                    'longitude' =>
                        $longitude,

                    'city' =>
                        $currentWeather['name']
                        ?? null,

                    'country' =>
                        $currentWeather['sys']['country']
                        ?? $countryCode,
                ],

                'current' => [

                    'temperature' =>
                        $currentWeather['main']['temp']
                        ?? null,

                    'feels_like' =>
                        $currentWeather['main']['feels_like']
                        ?? null,

                    'humidity' =>
                        $currentWeather['main']['humidity']
                        ?? null,

                    'pressure' =>
                        $currentWeather['main']['pressure']
                        ?? null,

                    'wind_speed' =>
                        $currentWeather['wind']['speed']
                        ?? null,

                    'wind_direction' =>
                        $currentWeather['wind']['deg']
                        ?? null,

                    'weather' =>
                        $currentWeather['weather'][0]
                        ?? null,

                    'clouds' =>
                        $currentWeather['clouds']['all']
                        ?? null,

                    'visibility' =>
                        $currentWeather['visibility']
                        ?? null,

                    'rain' =>
                        $currentWeather['rain']
                        ?? null,

                    'sunrise' =>
                        $currentWeather['sys']['sunrise']
                        ?? null,

                    'sunset' =>
                        $currentWeather['sys']['sunset']
                        ?? null,
                ],

                'daily' =>
                    $dailyForecast,

                'alerts' =>
                    $alerts,

                'updated_at' =>
                    now()->toIso8601String(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Fresh Cache
            |--------------------------------------------------------------------------
            |
            | Default: 30 minutes
            |
            */

            Cache::put(
                $freshCacheKey,
                $data,
                now()->addMinutes(
                    $this->cacheMinutes
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Last Successful Weather Data
            |--------------------------------------------------------------------------
            |
            | Stored permanently for API failure fallback.
            |
            */

            Cache::forever(
                $lastCacheKey,
                $data
            );

            return array_merge(
                $data,
                [
                    'source' => 'api',
                    'fallback' => false,
                ]
            );

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | API Failure Fallback
            |--------------------------------------------------------------------------
            */

            $lastWeather = Cache::get(
                $lastCacheKey
            );

            if ($lastWeather) {
                return array_merge(
                    $lastWeather,
                    [
                        'source' =>
                            'last_cached_data',

                        'fallback' =>
                            true,
                    ]
                );
            }

            throw new RuntimeException(
                'Weather service is currently unavailable and no cached weather data exists.'
            );
        }
    }

    /**
     * Convert OpenWeather 3-hour forecast
     * into daily forecast information.
     *
     * NOTE:
     * OpenWeather /forecast normally provides
     * approximately 5 days of forecast data.
     */
    protected function buildDailyForecast(
        array $forecastList
    ): array {

        $grouped = [];

        foreach ($forecastList as $item) {

            if (!isset($item['dt_txt'])) {
                continue;
            }

            $date = substr(
                $item['dt_txt'],
                0,
                10
            );

            $grouped[$date][] = $item;
        }

        $daily = [];

        foreach ($grouped as $date => $items) {

            $temperatures = [];

            $humidity = [];

            $windSpeeds = [];

            $rain = 0;

            $weatherConditions = [];

            foreach ($items as $item) {

                if (
                    isset(
                        $item['main']['temp']
                    )
                ) {
                    $temperatures[] =
                        (float) $item['main']['temp'];
                }

                if (
                    isset(
                        $item['main']['humidity']
                    )
                ) {
                    $humidity[] =
                        (float) $item['main']['humidity'];
                }

                if (
                    isset(
                        $item['wind']['speed']
                    )
                ) {
                    $windSpeeds[] =
                        (float) $item['wind']['speed'];
                }

                if (
                    isset(
                        $item['rain']['3h']
                    )
                ) {
                    $rain +=
                        (float) $item['rain']['3h'];
                }

                if (
                    isset(
                        $item['weather'][0]
                    )
                ) {
                    $weatherConditions[] =
                        $item['weather'][0];
                }
            }

            $daily[] = [

                'date' =>
                    $date,

                'temperature' => [

                    'min' =>
                        !empty($temperatures)
                            ? round(
                                min($temperatures),
                                2
                            )
                            : null,

                    'max' =>
                        !empty($temperatures)
                            ? round(
                                max($temperatures),
                                2
                            )
                            : null,

                    'average' =>
                        !empty($temperatures)
                            ? round(
                                array_sum($temperatures)
                                /
                                count($temperatures),
                                2
                            )
                            : null,
                ],

                'humidity' =>
                    !empty($humidity)
                        ? round(
                            array_sum($humidity)
                            /
                            count($humidity),
                            2
                        )
                        : null,

                'wind_speed' =>
                    !empty($windSpeeds)
                        ? round(
                            array_sum($windSpeeds)
                            /
                            count($windSpeeds),
                            2
                        )
                        : null,

                'rain' =>
                    round($rain, 2),

                'weather' =>
                    $weatherConditions[0]
                    ?? null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Return maximum 7 days
        |--------------------------------------------------------------------------
        */

        return array_slice(
            $daily,
            0,
            7
        );
    }

    /**
     * Build adverse weather alerts.
     *
     * Uses current weather + 3-hour forecast.
     */
    protected function buildWeatherAlerts(
        array $currentWeather,
        array $forecastList
    ): array {

        $alerts = [];

        /*
        |--------------------------------------------------------------------------
        | Current Weather Information
        |--------------------------------------------------------------------------
        */

        $currentWeatherId =
            $currentWeather['weather'][0]['id']
            ?? null;

        $currentWeatherMain =
            strtolower(
                $currentWeather['weather'][0]['main']
                ?? ''
            );

        /*
        |--------------------------------------------------------------------------
        | Current Thunderstorm
        |--------------------------------------------------------------------------
        */

        if (
            $currentWeatherId !== null &&
            $currentWeatherId >= 200 &&
            $currentWeatherId < 300
        ) {
            $alerts[] = [

                'type' =>
                    'thunderstorm',

                'severity' =>
                    'high',

                'title' =>
                    'Thunderstorm Alert',

                'message' =>
                    'Thunderstorm conditions are currently present.',

                'date' =>
                    now()->toDateString(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Current Rain
        |--------------------------------------------------------------------------
        */

        if (
            $currentWeatherId !== null &&
            $currentWeatherId >= 500 &&
            $currentWeatherId < 600
        ) {
            $severity =
                $currentWeatherId >= 502
                    ? 'high'
                    : 'moderate';

            $alerts[] = [

                'type' =>
                    'rain',

                'severity' =>
                    $severity,

                'title' =>
                    'Rain Alert',

                'message' =>
                    'Rain is currently expected. Consider protecting crops and farm equipment.',

                'date' =>
                    now()->toDateString(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Extreme Weather
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $currentWeatherMain,
                [
                    'tornado',
                    'squall',
                ],
                true
            )
        ) {
            $alerts[] = [

                'type' =>
                    'extreme_weather',

                'severity' =>
                    'critical',

                'title' =>
                    'Extreme Weather Alert',

                'message' =>
                    'Extreme weather conditions are currently reported.',

                'date' =>
                    now()->toDateString(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Strong Wind
        |--------------------------------------------------------------------------
        */

        $windSpeed =
            (float) (
                $currentWeather['wind']['speed']
                ?? 0
            );

        if ($windSpeed >= 15) {
            $alerts[] = [

                'type' =>
                    'strong_wind',

                'severity' =>
                    'high',

                'title' =>
                    'Strong Wind Alert',

                'message' =>
                    'Strong winds are currently expected. Secure vulnerable crops and equipment.',

                'date' =>
                    now()->toDateString(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Forecast Alerts
        |--------------------------------------------------------------------------
        */

        foreach ($forecastList as $item) {

            $weatherId =
                $item['weather'][0]['id']
                ?? null;

            $weatherMain =
                strtolower(
                    $item['weather'][0]['main']
                    ?? ''
                );

            $date =
                isset($item['dt_txt'])
                    ? substr(
                        $item['dt_txt'],
                        0,
                        10
                    )
                    : null;

            /*
            |--------------------------------------------------------------------------
            | Forecast Thunderstorm
            |--------------------------------------------------------------------------
            */

            if (
                $weatherId !== null &&
                $weatherId >= 200 &&
                $weatherId < 300
            ) {
                $alerts[] = [

                    'type' =>
                        'thunderstorm',

                    'severity' =>
                        'high',

                    'title' =>
                        'Upcoming Thunderstorm',

                    'message' =>
                        'Thunderstorm conditions may occur according to the forecast.',

                    'date' =>
                        $date,
                ];

                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Forecast Heavy Rain
            |--------------------------------------------------------------------------
            */

            if (
                $weatherId !== null &&
                $weatherId >= 502 &&
                $weatherId <= 504
            ) {
                $alerts[] = [

                    'type' =>
                        'heavy_rain',

                    'severity' =>
                        'moderate',

                    'title' =>
                        'Heavy Rain Forecast',

                    'message' =>
                        'Heavy rainfall may occur. Consider appropriate crop protection.',

                    'date' =>
                        $date,
                ];

                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Forecast Extreme Weather
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $weatherMain,
                    [
                        'tornado',
                        'squall',
                    ],
                    true
                )
            ) {
                $alerts[] = [

                    'type' =>
                        'extreme_weather',

                    'severity' =>
                        'critical',

                    'title' =>
                        'Extreme Weather Forecast',

                    'message' =>
                        'Extreme weather conditions may occur according to the forecast.',

                    'date' =>
                        $date,
                ];

                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Alerts
        |--------------------------------------------------------------------------
        */

        return collect($alerts)
            ->unique(
                fn ($alert) =>
                    $alert['type'] .
                    '|' .
                    ($alert['date'] ?? '')
            )
            ->values()
            ->toArray();
    }
}