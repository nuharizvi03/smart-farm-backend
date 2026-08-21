<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Expense;
use Illuminate\Http\Request;

class AnnualProfitController extends Controller
{
    /**
     * Display annual profit analysis.
     */
    public function show(Request $request, int $year)
    {
        // Get all crops planted in the selected calendar year
        $crops = Crop::whereYear('planting_date', $year)
            ->with([
                'expenses',
                'harvests.sales',
                'harvests.postHarvestLosses',
            ])
            ->get();

        $cropData = $crops->map(function ($crop) {

            // Total revenue from all sales
            $totalRevenue = $crop->harvests
                ->flatMap(function ($harvest) {
                    return $harvest->sales;
                })
                ->sum(function ($sale) {
                    return
                        (float) $sale->quantity_sold
                        * (float) $sale->price_per_unit;
                });

            // Crop-specific expenses only
            $cropExpenses = $crop->expenses
                ->sum('amount');

            // Post-harvest financial losses
            $postHarvestLossAmount = $crop->harvests
                ->flatMap(function ($harvest) {
                    return $harvest->postHarvestLosses;
                })
                ->sum('loss_amount');

            // Profit for this crop
            $profit =
                $totalRevenue
                - $cropExpenses
                - $postHarvestLossAmount;

            return [
                'crop_id' => $crop->id,
                'crop_name' => $crop->crop_name,
                'season' => $crop->season,

                'total_revenue' => (float) $totalRevenue,
                'crop_expenses' => (float) $cropExpenses,
                'post_harvest_loss_amount' =>
                    (float) $postHarvestLossAmount,

                'profit' => (float) $profit,

                'profit_status' =>
                    $profit > 0
                        ? 'profit'
                        : ($profit < 0
                            ? 'loss'
                            : 'break_even'),
            ];
        });

        // Sum of all crop profits
        $totalCropProfit = $cropData->sum('profit');

        /*
         * Farm-wide expenses only.
         *
         * crop_id = NULL means the expense belongs to
         * the farm and not to a specific crop.
         */
        $farmWideExpenses = Expense::whereNull('crop_id')
            ->whereYear('expense_date', $year)
            ->sum('amount');

        // Final annual profit
        $annualProfit =
            $totalCropProfit
            - $farmWideExpenses;

        return response()->json([
            'success' => true,
            'message' => 'Annual profit analysis retrieved successfully.',

            'data' => [
                'year' => $year,

                'crop_count' => $crops->count(),

                'crops' => $cropData->values(),

                'financials' => [
                    'total_crop_profit' =>
                        (float) $totalCropProfit,

                    'farm_wide_expenses' =>
                        (float) $farmWideExpenses,

                    'annual_profit' =>
                        (float) $annualProfit,

                    'profit_status' =>
                        $annualProfit > 0
                            ? 'profit'
                            : ($annualProfit < 0
                                ? 'loss'
                                : 'break_even'),
                ],
            ],
        ]);
    }
}