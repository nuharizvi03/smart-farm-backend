<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Throwable;

class FarmWeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(
        WeatherService $weatherService
    ) {
        $this->weatherService = $weatherService;
    }

    /**
     * Get weather for a farm.
     */
    public function show(Farm $farm): JsonResponse
    {
        try {
            $weather = $this->weatherService
                ->getWeatherByDistrict(
                    $farm->district,
                    'LK'
                );

            return response()->json([
                'success' => true,
                'message' => 'Weather data retrieved successfully.',
                'data' => $weather,
            ]);

        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve weather data.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }
}