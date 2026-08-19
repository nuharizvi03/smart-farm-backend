<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInputApplicationRequest;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\InputApplication;
use Illuminate\Support\Facades\DB;

class InputApplicationController extends Controller
{
    /**
     * Display all input applications for a crop.
     */
    public function index(Crop $crop)
    {
        $applications = $crop->inputApplications()
            ->with('agrochemicalProduct')
            ->orderByDesc('application_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Input applications retrieved successfully.',
            'data' => $applications,
        ]);
    }

    /**
     * Display cumulative input usage and dosage threshold alerts for a crop.
     */
    public function summary(Crop $crop)
    {
        $fertilizerTotal = $crop->inputApplications()
            ->where('input_type', 'fertilizer')
            ->sum('quantity_applied');

        $pesticideTotal = $crop->inputApplications()
            ->where('input_type', 'pesticide')
            ->sum('quantity_applied');

        $herbicideTotal = $crop->inputApplications()
            ->where('input_type', 'herbicide')
            ->sum('quantity_applied');

        $fertilizerRecommendation = $crop->inputApplications()
            ->where('input_type', 'fertilizer')
            ->whereNotNull('recommended_dosage')
            ->latest('application_date')
            ->first();

        $pesticideRecommendation = $crop->inputApplications()
            ->where('input_type', 'pesticide')
            ->whereNotNull('recommended_dosage')
            ->latest('application_date')
            ->first();

        $herbicideRecommendation = $crop->inputApplications()
            ->where('input_type', 'herbicide')
            ->whereNotNull('recommended_dosage')
            ->latest('application_date')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Input summary retrieved successfully.',
            'data' => [
                'fertilizer' => [
                    'total_quantity' => (float) $fertilizerTotal,
                    'unit' => 'kg',
                    'recommended_dosage' => $fertilizerRecommendation
                        ? (float) $fertilizerRecommendation->recommended_dosage
                        : null,
                    'dosage_unit' => $fertilizerRecommendation?->dosage_unit,
                    'threshold_exceeded' => $fertilizerRecommendation
                        ? $fertilizerTotal > $fertilizerRecommendation->recommended_dosage
                        : false,
                ],

                'pesticide' => [
                    'total_quantity' => (float) $pesticideTotal,
                    'unit' => 'L',
                    'recommended_dosage' => $pesticideRecommendation
                        ? (float) $pesticideRecommendation->recommended_dosage
                        : null,
                    'dosage_unit' => $pesticideRecommendation?->dosage_unit,
                    'threshold_exceeded' => $pesticideRecommendation
                        ? $pesticideTotal > $pesticideRecommendation->recommended_dosage
                        : false,
                ],

                'herbicide' => [
                    'total_quantity' => (float) $herbicideTotal,
                    'unit' => 'L',
                    'recommended_dosage' => $herbicideRecommendation
                        ? (float) $herbicideRecommendation->recommended_dosage
                        : null,
                    'dosage_unit' => $herbicideRecommendation?->dosage_unit,
                    'threshold_exceeded' => $herbicideRecommendation
                        ? $herbicideTotal > $herbicideRecommendation->recommended_dosage
                        : false,
                ],
            ],
        ]);
    }

    /**
     * Store a new input application and automatically create an expense.
     */
    public function store(
        StoreInputApplicationRequest $request,
        Crop $crop
    ) {
        $validated = $request->validated();

        $totalCost = $validated['total_cost']
            ?? $validated['quantity_applied'] * $validated['unit_cost'];

        $application = DB::transaction(function () use (
            $validated,
            $crop,
            $totalCost
        ) {
            $application = $crop->inputApplications()->create([
                'agrochemical_product_id' =>
                    $validated['agrochemical_product_id'] ?? null,

                'input_type' => $validated['input_type'],

                'product_name' => $validated['product_name'],

                'application_date' => $validated['application_date'],

                'quantity_applied' => $validated['quantity_applied'],

                'unit' => $validated['unit'],

                'unit_cost' => $validated['unit_cost'],

                'total_cost' => $totalCost,

                'recommended_dosage' =>
                    $validated['recommended_dosage'] ?? null,

                'dosage_unit' =>
                    $validated['dosage_unit'] ?? null,

                'next_application_date' =>
                    $validated['next_application_date'] ?? null,

                'notes' =>
                    $validated['notes'] ?? null,
            ]);

            $category = $application->input_type === 'fertilizer'
                ? 'Fertilizer'
                : 'Pesticide';

            Expense::create([
                'farm_id' => $crop->plot->farm_id,

                'crop_id' => $crop->id,

                'category' => $category,

                'amount' => $totalCost,

                'expense_date' => $application->application_date,

                'description' =>
                    $application->product_name . ' input application',
            ]);

            return $application;
        });

        return response()->json([
            'success' => true,
            'message' => 'Input application and expense created successfully.',
            'data' => $application->load('agrochemicalProduct'),
        ], 201);
    }

    /**
     * Display one input application.
     */
    public function show(
        Crop $crop,
        InputApplication $inputApplication
    ) {
        if ($inputApplication->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Input application does not belong to this crop.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inputApplication->load('agrochemicalProduct'),
        ]);
    }

    /**
     * Update an input application.
     */
    public function update(
        StoreInputApplicationRequest $request,
        Crop $crop,
        InputApplication $inputApplication
    ) {
        if ($inputApplication->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Input application does not belong to this crop.',
            ], 404);
        }

        $validated = $request->validated();

        $totalCost = $validated['total_cost']
            ?? $validated['quantity_applied'] * $validated['unit_cost'];

        $inputApplication->update([
            'agrochemical_product_id' =>
                $validated['agrochemical_product_id'] ?? null,

            'input_type' => $validated['input_type'],

            'product_name' => $validated['product_name'],

            'application_date' => $validated['application_date'],

            'quantity_applied' => $validated['quantity_applied'],

            'unit' => $validated['unit'],

            'unit_cost' => $validated['unit_cost'],

            'total_cost' => $totalCost,

            'recommended_dosage' =>
                $validated['recommended_dosage'] ?? null,

            'dosage_unit' =>
                $validated['dosage_unit'] ?? null,

            'next_application_date' =>
                $validated['next_application_date'] ?? null,

            'notes' =>
                $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Input application updated successfully.',
            'data' => $inputApplication
                ->fresh()
                ->load('agrochemicalProduct'),
        ]);
    }

    /**
     * Delete an input application.
     */
    public function destroy(
        Crop $crop,
        InputApplication $inputApplication
    ) {
        if ($inputApplication->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Input application does not belong to this crop.',
            ], 404);
        }

        $inputApplication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Input application deleted successfully.',
        ]);
    }
}