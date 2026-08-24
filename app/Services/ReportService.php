<?php

namespace App\Services;

use App\Models\Crop;

class ReportService
{
    /**
     * FR-10.1
     * Generate Crop Profit Report data.
     */
    public function cropProfit(Crop $crop): array
    {
        $crop->load([
            'plot.farm',
            'expenses',
            'harvests.sales',
            'harvests.postHarvestLosses',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        $expenses = $crop->expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'date' => $expense->expense_date,
                'category' => $expense->category,
                'description' => $expense->description,
                'amount' => round((float) $expense->amount, 2),
            ];
        })->values();

        $totalExpenses = $expenses->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Sales / Revenue
        |--------------------------------------------------------------------------
        */

        $sales = $crop->harvests
            ->flatMap(function ($harvest) {
                return $harvest->sales;
            })
            ->map(function ($sale) {
                $amount =
                    (float) $sale->quantity_sold
                    * (float) $sale->price_per_unit;

                return [
                    'id' => $sale->id,
                    'buyer_name' => $sale->buyer_name,
                    'sale_date' => $sale->sale_date,
                    'quantity_sold' =>
                        round((float) $sale->quantity_sold, 2),
                    'price_per_unit' =>
                        round((float) $sale->price_per_unit, 2),
                    'amount' => round($amount, 2),
                    'payment_status' => $sale->payment_status,
                ];
            })
            ->values();

        $totalRevenue = $sales->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Post-Harvest Losses
        |--------------------------------------------------------------------------
        */

        $losses = $crop->harvests
            ->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            });

        $totalPostHarvestLoss =
            $losses->sum('loss_amount');

        /*
        |--------------------------------------------------------------------------
        | Profit
        |--------------------------------------------------------------------------
        */

        $totalExpensesWithLoss =
            $totalExpenses + $totalPostHarvestLoss;

        $profit =
            $totalRevenue - $totalExpensesWithLoss;

        $profitStatus =
            $profit > 0
                ? 'profit'
                : ($profit < 0
                    ? 'loss'
                    : 'break_even');

        return [
            'crop' => [
                'id' => $crop->id,
                'crop_name' => $crop->crop_name,
                'variety' => $crop->variety,
                'season' => $crop->season,
                'planting_date' => $crop->planting_date,
                'expected_harvest_date' =>
                    $crop->expected_harvest_date,
                'status' => $crop->status,
            ],

            'farm' => [
                'id' => $crop->plot?->farm?->id,
                'name' => $crop->plot?->farm?->name,
            ],

            'expenses' => $expenses,

            'revenue_entries' => $sales,

            'financial_summary' => [
                'total_revenue' =>
                    round($totalRevenue, 2),

                'total_expenses' =>
                    round($totalExpenses, 2),

                'post_harvest_loss_amount' =>
                    round((float) $totalPostHarvestLoss, 2),

                'total_cost' =>
                    round($totalExpensesWithLoss, 2),

                'profit' =>
                    round($profit, 2),

                'profit_status' =>
                    $profitStatus,
            ],
        ];
    }
}