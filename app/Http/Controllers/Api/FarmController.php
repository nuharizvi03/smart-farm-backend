<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FarmController extends Controller
{
    /**
     * Display a listing of the user's farms.
     */
    public function index(Request $request): JsonResponse
    {
        $farms = Farm::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Farms retrieved successfully.',
            'data' => $farms,
        ]);
    }


    /**
     * Store a newly created farm.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'farm_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'location' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'district' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'province' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'total_area' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'area_unit' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $farm = Farm::create([
                'user_id' => $request->user()->id,

                'farm_name' =>
                    $request->farm_name,

                'location' =>
                    $request->location,

                'district' =>
                    $request->district,

                'province' =>
                    $request->province,

                'total_area' =>
                    $request->total_area,

                'area_unit' =>
                    $request->area_unit ?? 'acres',

                'description' =>
                    $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Farm created successfully.',
                'data' => $farm,
            ], 201);

        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to create farm.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified farm.
     */
    public function show(
        Request $request,
        Farm $farm
    ): JsonResponse {

        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Farm retrieved successfully.',
            'data' => $farm,
        ]);
    }


    /**
     * Update the specified farm.
     *
     * If district/location changes, the weather cache
     * associated with the OLD location is cleared.
     */
    public function update(
        Request $request,
        Farm $farm
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this farm.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'farm_name' => [
                    'sometimes',
                    'string',
                    'max:255',
                ],

                'location' => [
                    'sometimes',
                    'string',
                    'max:255',
                ],

                'district' => [
                    'sometimes',
                    'string',
                    'max:100',
                ],

                'province' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'total_area' => [
                    'sometimes',
                    'numeric',
                    'min:0',
                ],

                'area_unit' => [
                    'sometimes',
                    'string',
                    'max:50',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Store OLD Farm Location
        |--------------------------------------------------------------------------
        |
        | We need the old district before updating the farm.
        |
        */

        $oldDistrict = $farm->district;

        /*
        |--------------------------------------------------------------------------
        | Determine Whether Weather Location Changed
        |--------------------------------------------------------------------------
        */

        $districtChanged =
            $request->has('district') &&
            $request->district !== $oldDistrict;

        $locationChanged =
            $request->has('location') &&
            $request->location !== $farm->location;

        try {

            /*
            |--------------------------------------------------------------------------
            | Update Farm
            |--------------------------------------------------------------------------
            */

            $farm->update(
                $request->only([
                    'farm_name',
                    'location',
                    'district',
                    'province',
                    'total_area',
                    'area_unit',
                    'description',
                ])
            );

            /*
            |--------------------------------------------------------------------------
            | Clear Weather Cache
            |--------------------------------------------------------------------------
            |
            | The WeatherService uses coordinate-based cache keys.
            |
            | We cannot simply clear the new district's cache here,
            | because the old district may have cached data.
            |
            | The WeatherController will obtain the new coordinates
            | after the district changes, so the new location will
            | automatically use its own cache.
            |
            */

            if ($districtChanged || $locationChanged) {

                /*
                |----------------------------------------------------------------------
                | Clear geocoding cache for old district
                |----------------------------------------------------------------------
                */

                $oldQuery = $oldDistrict . ',LK';

                $oldGeocodingKey =
                    'weather:geocoding:' .
                    \Illuminate\Support\Str::slug(
                        strtolower($oldQuery)
                    );

                Cache::forget($oldGeocodingKey);
            }

            return response()->json([
                'success' => true,
                'message' => 'Farm updated successfully.',
                'data' => $farm->fresh(),
                'weather_location_changed' =>
                    $districtChanged || $locationChanged,
            ]);

        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update farm.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified farm.
     */
    public function destroy(
        Request $request,
        Farm $farm
    ): JsonResponse {

        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this farm.',
            ], 403);
        }

        try {

            $farm->delete();

            return response()->json([
                'success' => true,
                'message' => 'Farm deleted successfully.',
            ]);

        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete farm.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}