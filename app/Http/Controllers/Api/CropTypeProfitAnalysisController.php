<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;

class CropTypeProfitAnalysisController extends Controller
{
    /**
     * Analyze the profitability of crop types within a selected period.
     *
     * Example:
     * GET /api/profit-analysis/crop-types?start_date=2026-01-01&end_date=2026-12-31
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $crops = Crop::with([
            'expenses',
            'harvests.sales',
            'harvests.postHarvestLosses',
        ])
            ->whereBetween(
                'planting_date',
                [
                    $validated['start_date'],
                    $validated['end_date'],
                ]
            )
            ->get();

        $cropTypeAnalysis = $crops
            ->groupBy('crop_name')
            ->map(function ($crops, $cropName) {

                $totalRevenue = 0;
                $totalCropExpenses = 0;
                $totalPostHarvestLossAmount = 0;

                foreach ($crops as $crop) {
                    $harvests = $crop->harvests;

                    $revenue = $harvests
                        ->flatMap(function ($harvest) {
                            return $harvest->sales;
                        })
                        ->sum(function ($sale) {
                            return
                                (float) $sale->quantity_sold
                                * (float) $sale->price_per_unit;
                        });

                    $cropExpenses = $crop->expenses
                        ->sum('amount');

                    $postHarvestLossAmount = $harvests
                        ->flatMap(function ($harvest) {
                            return $harvest->postHarvestLosses;
                        })
                        ->sum('loss_amount');

                    $totalRevenue += (float) $revenue;
                    $totalCropExpenses += (float) $cropExpenses;
                    $totalPostHarvestLossAmount +=
                        (float) $postHarvestLossAmount;
                }

                $totalExpenses =
                    $totalCropExpenses
                    + $totalPostHarvestLossAmount;

                $profit =
                    $totalRevenue
                    - $totalExpenses;

                $profitMargin = $totalRevenue > 0
                    ? ($profit / $totalRevenue) * 100
                    : 0;

                $profitStatus = $profit > 0
                    ? 'profit'
                    : ($profit < 0
                        ? 'loss'
                        : 'break_even');

                return [
                    'crop_name' => $cropName,

                    'crop_plan_count' =>
                        $crops->count(),

                    'total_revenue' =>
                        round($totalRevenue, 2),

                    'crop_expenses' =>
                        round($totalCropExpenses, 2),

                    'post_harvest_loss_amount' =>
                        round($totalPostHarvestLossAmount, 2),

                    'total_expenses' =>
                        round($totalExpenses, 2),

                    'profit' =>
                        round($profit, 2),

                    'profit_margin_percentage' =>
                        round($profitMargin, 2),

                    'profit_status' =>
                        $profitStatus,
                ];
            })
            ->sortByDesc('profit')
            ->values();

        $mostProfitable = $cropTypeAnalysis->first();

        $leastProfitable = $cropTypeAnalysis
            ->sortBy('profit')
            ->first();

        return response()->json([
            'success' => true,
            'message' =>
                'Crop type profitability analysis retrieved successfully.',

            'data' => [
                'period' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ],

                'crop_types' => $cropTypeAnalysis,

                'most_profitable_crop_type' => $mostProfitable,

                'least_profitable_crop_type' => $leastProfitable,
            ],
        ]);
    }
}