<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;

class CropProfitComparisonController extends Controller
{
    /**
     * Compare profitability of two or more crops.
     *
     * Example:
     * GET /api/crops/profit-comparison?crop_ids[]=2&crop_ids[]=3
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'crop_ids' => ['required', 'array', 'min:2'],
            'crop_ids.*' => ['integer', 'exists:crops,id'],
        ]);

        $crops = Crop::with([
            'expenses',
            'harvests.sales',
            'harvests.postHarvestLosses',
        ])
            ->whereIn('id', $validated['crop_ids'])
            ->get();

        $comparison = $crops->map(function ($crop) {

            $harvests = $crop->harvests;

            /*
            |--------------------------------------------------------------------------
            | Total harvested quantity
            |--------------------------------------------------------------------------
            */

            $totalHarvested = (float) $harvests
                ->sum('quantity_harvested');

            /*
            |--------------------------------------------------------------------------
            | Total revenue
            |--------------------------------------------------------------------------
            */

            $totalRevenue = (float) $harvests
                ->flatMap(function ($harvest) {
                    return $harvest->sales;
                })
                ->sum(function ($sale) {
                    return
                        (float) $sale->quantity_sold
                        * (float) $sale->price_per_unit;
                });

            /*
            |--------------------------------------------------------------------------
            | Crop-specific expenses
            |--------------------------------------------------------------------------
            */

            $cropExpenses = (float) $crop
                ->expenses
                ->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | Post-harvest loss amount
            |--------------------------------------------------------------------------
            */

            $postHarvestLossAmount = (float) $harvests
                ->flatMap(function ($harvest) {
                    return $harvest->postHarvestLosses;
                })
                ->sum('loss_amount');

            /*
            |--------------------------------------------------------------------------
            | Profit calculation
            |
            | Profit = Revenue - Crop Expenses - Post-Harvest Loss Amount
            |--------------------------------------------------------------------------
            */

            $totalExpenses =
                $cropExpenses
                + $postHarvestLossAmount;

            $profit =
                $totalRevenue
                - $totalExpenses;

            /*
            |--------------------------------------------------------------------------
            | Profit margin
            |--------------------------------------------------------------------------
            */

            $profitMargin = $totalRevenue > 0
                ? ($profit / $totalRevenue) * 100
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Per-unit profit
            |--------------------------------------------------------------------------
            */

            $perUnitProfit = $totalHarvested > 0
                ? $profit / $totalHarvested
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Profit status
            |--------------------------------------------------------------------------
            */

            $profitStatus = $profit > 0
                ? 'profit'
                : ($profit < 0
                    ? 'loss'
                    : 'break_even');

            return [
                'crop_id' => $crop->id,
                'crop_name' => $crop->crop_name,
                'season' => $crop->season,

                'total_harvested' =>
                    round($totalHarvested, 2),

                'total_revenue' =>
                    round($totalRevenue, 2),

                'crop_expenses' =>
                    round($cropExpenses, 2),

                'post_harvest_loss_amount' =>
                    round($postHarvestLossAmount, 2),

                'total_expenses' =>
                    round($totalExpenses, 2),

                'profit' =>
                    round($profit, 2),

                'profit_margin_percentage' =>
                    round($profitMargin, 2),

                'per_unit_profit' =>
                    round($perUnitProfit, 2),

                'profit_status' =>
                    $profitStatus,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Crop profitability comparison retrieved successfully.',

            'data' => $comparison
                ->sortByDesc('profit')
                ->values(),
        ]);
    }
}