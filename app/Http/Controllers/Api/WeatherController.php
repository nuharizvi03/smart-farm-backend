<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get weather by district.
     *
     * Example:
     * GET /api/weather?district=Kandy
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'district' => [
                'required',
                'string',
                'max:100',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ]);

        try {
            $weather = $this->weatherService->getWeatherByDistrict(
                $request->district,
                $request->country_code ?? 'LK'
            );

            return response()->json([
                'success' => true,
                'message' => 'Weather data retrieved successfully.',
                'data' => $weather,
            ]);
        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    /**
     * Get weather for a specific farm.
     *
     * The farm's current district is automatically used.
     *
     * Example:
     * GET /api/farms/1/weather
     */
    public function farmWeather(Request $request, Farm $farm): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        |
        | Only the owner of the farm can retrieve its weather.
        |
        */

        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Farm District
        |--------------------------------------------------------------------------
        */

        if (empty($farm->district)) {
            return response()->json([
                'success' => false,
                'message' => 'Farm district is not set.',
            ], 422);
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Get Weather Using Current Farm District
            |--------------------------------------------------------------------------
            */

            $weather = $this->weatherService->getWeatherByDistrict(
                $farm->district,
                'LK'
            );

            /*
            |--------------------------------------------------------------------------
            | Add Farm Information
            |--------------------------------------------------------------------------
            |
            | This makes it clear which farm the weather belongs to.
            |
            */

            $weather['farm'] = [
                'id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'district' => $farm->district,
                'location' => $farm->location,
                'province' => $farm->province,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Farm weather data retrieved successfully.',
                'data' => $weather,
            ]);

        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve farm weather data.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }
}