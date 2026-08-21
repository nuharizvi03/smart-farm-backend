<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;

class CropFinancialSummaryController extends Controller
{
    /**
     * Display the financial and inventory summary for a crop.
     *
     * Includes:
     * - FR-06.1 Per-crop profit
     * - FR-06.2 Profit margin
     * - FR-06.5 Profit/Loss indicator
     * - FR-06.6 Per-unit profit
     */
    public function show(Crop $crop)
    {
        $harvests = $crop->harvests()
            ->with([
                'sales',
                'postHarvestLosses',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Inventory calculations
        |--------------------------------------------------------------------------
        */

        $totalHarvested = (float) $harvests
            ->sum('quantity_harvested');

        $totalSold = (float) $harvests
            ->flatMap(function ($harvest) {
                return $harvest->sales;
            })
            ->sum('quantity_sold');

        $totalPostHarvestLoss = (float) $harvests
            ->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            })
            ->sum('quantity_lost');

        $unsoldQuantity =
            $totalHarvested
            - $totalSold
            - $totalPostHarvestLoss;

        /*
        |--------------------------------------------------------------------------
        | Revenue calculation
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
        | Expense calculations
        |--------------------------------------------------------------------------
        */

        $cropExpenses = (float) $crop
            ->expenses()
            ->sum('amount');

        $totalPostHarvestLossAmount = (float) $harvests
            ->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            })
            ->sum('loss_amount');

        /*
        |--------------------------------------------------------------------------
        | Total expenses and profit
        |--------------------------------------------------------------------------
        */

        $totalExpenses =
            $cropExpenses
            + $totalPostHarvestLossAmount;

        $profit =
            $totalRevenue
            - $totalExpenses;

        /*
        |--------------------------------------------------------------------------
        | FR-06.2 — Profit Margin
        |
        | Profit Margin = (Profit / Total Revenue) × 100
        |--------------------------------------------------------------------------
        */

        $profitMargin = $totalRevenue > 0
            ? ($profit / $totalRevenue) * 100
            : 0;

        /*
        |--------------------------------------------------------------------------
        | FR-06.6 — Per-Unit Profit
        |
        | Per-Unit Profit = Profit / Total Yield
        |--------------------------------------------------------------------------
        */

        $perUnitProfit = $totalHarvested > 0
            ? $profit / $totalHarvested
            : 0;

        /*
        |--------------------------------------------------------------------------
        | FR-06.5 — Profit/Loss Indicator
        |--------------------------------------------------------------------------
        */

        $profitStatus = $profit > 0
            ? 'profit'
            : ($profit < 0
                ? 'loss'
                : 'break_even');

        return response()->json([
            'success' => true,
            'message' => 'Crop financial summary retrieved successfully.',

            'data' => [
                'crop_id' => $crop->id,
                'crop_name' => $crop->crop_name,

                'inventory' => [
                    'total_harvested' => round($totalHarvested, 2),
                    'total_sold' => round($totalSold, 2),
                    'total_post_harvest_loss' =>
                        round($totalPostHarvestLoss, 2),
                    'unsold_quantity' =>
                        round($unsoldQuantity, 2),
                ],

                'financials' => [
                    'total_revenue' =>
                        round($totalRevenue, 2),

                    'crop_expenses' =>
                        round($cropExpenses, 2),

                    'post_harvest_loss_amount' =>
                        round($totalPostHarvestLossAmount, 2),

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
                ],
            ],
        ]);
    }
}