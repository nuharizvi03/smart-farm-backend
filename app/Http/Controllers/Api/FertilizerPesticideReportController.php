<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\InputApplication;
use Illuminate\Http\Request;

class FertilizerPesticideReportController extends Controller
{
    /**
     * FR-10.6
     * Generate Fertilizer & Pesticide Usage Report per Crop Plan.
     *
     * GET /api/reports/crops/{crop}/input-usage
     */
    public function show(Request $request, Crop $crop)
    {
        $validated = $request->validate([
            'input_type' => [
                'nullable',
                'in:fertilizer,pesticide',
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
        ]);

        $query = InputApplication::query()
            ->where('crop_id', $crop->id)
            ->orderBy('application_date');

        /*
        |--------------------------------------------------------------------------
        | Filter by input type
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['input_type'])) {
            $query->where(
                'input_type',
                $validated['input_type']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by date range
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['from_date'])) {
            $query->whereDate(
                'application_date',
                '>=',
                $validated['from_date']
            );
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate(
                'application_date',
                '<=',
                $validated['to_date']
            );
        }

        $applications = $query->get();

        /*
        |--------------------------------------------------------------------------
        | Usage Entries
        |--------------------------------------------------------------------------
        */

        $usage = $applications->map(function ($application) {
            return [
                'id' => $application->id,

                'product' => $application->product_name,

                'input_type' => $application->input_type,

                'application_date' =>
                    $application->application_date,

                'quantity' =>
                    (float) $application->quantity_applied,

                'unit' =>
                    $application->unit,

                'unit_cost' =>
                    round(
                        (float) $application->unit_cost,
                        2
                    ),

                'total_cost' =>
                    round(
                        (float) $application->total_cost,
                        2
                    ),
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalCost = $applications->sum(
            fn ($application) =>
                (float) $application->total_cost
        );

        $totalApplications = $applications->count();

        $fertilizerCost = $applications
            ->where('input_type', 'fertilizer')
            ->sum(
                fn ($application) =>
                    (float) $application->total_cost
            );

        $pesticideCost = $applications
            ->where('input_type', 'pesticide')
            ->sum(
                fn ($application) =>
                    (float) $application->total_cost
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Fertilizer and pesticide usage report generated successfully.',

            'data' => [
                'crop' => [
                    'id' => $crop->id,
                    'crop_name' => $crop->crop_name,
                    'variety' => $crop->variety,
                    'season' => $crop->season,
                    'status' => $crop->status,
                ],

                'filters' => [
                    'input_type' =>
                        $validated['input_type'] ?? null,

                    'from_date' =>
                        $validated['from_date'] ?? null,

                    'to_date' =>
                        $validated['to_date'] ?? null,
                ],

                'summary' => [
                    'total_applications' =>
                        $totalApplications,

                    'total_cost' =>
                        round(
                            (float) $totalCost,
                            2
                        ),

                    'fertilizer_cost' =>
                        round(
                            (float) $fertilizerCost,
                            2
                        ),

                    'pesticide_cost' =>
                        round(
                            (float) $pesticideCost,
                            2
                        ),
                ],

                'usage' => $usage,
            ],
        ]);
    }
}