<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Expense;
use Illuminate\Http\Request;

class SeasonProfitController extends Controller
{
    /**
     * Calculate profit for all crops in a specific season.
     *
     * FR-06.3:
     * Season Profit =
     * SUM(Per-Crop Profits)
     * - SUM(Farm-Wide Expenses)
     */
    public function show(Request $request, string $season)
    {
        /*
        |--------------------------------------------------------------------------
        | Get all crops for the selected season
        |--------------------------------------------------------------------------
        */

        $crops = Crop::where('season', $season)
            ->with([
                'harvests.sales',
                'harvests.postHarvestLosses',
                'expenses',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Calculate profit for each crop
        |--------------------------------------------------------------------------
        */

        $cropSummaries = $crops->map(function ($crop) {

            // Get all harvests
            $harvests = $crop->harvests;

            // Get all sales from all harvests
            $sales = $harvests->flatMap(function ($harvest) {
                return $harvest->sales;
            });

            // Get all post-harvest losses
            $losses = $harvests->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            });

            /*
            |--------------------------------------------------------------------------
            | Total Revenue
            |--------------------------------------------------------------------------
            */

            $totalRevenue = $sales->sum(function ($sale) {
                return
                    (float) $sale->quantity_sold
                    * (float) $sale->price_per_unit;
            });

            /*
            |--------------------------------------------------------------------------
            | Crop Expenses
            |--------------------------------------------------------------------------
            */

            $cropExpenses = (float) $crop->expenses
                ->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | Post-Harvest Loss Amount
            |--------------------------------------------------------------------------
            */

            $postHarvestLossAmount = (float) $losses
                ->sum('loss_amount');

            /*
            |--------------------------------------------------------------------------
            | Total Crop Expenses
            |--------------------------------------------------------------------------
            */

            $totalExpenses =
                $cropExpenses
                + $postHarvestLossAmount;

            /*
            |--------------------------------------------------------------------------
            | Crop Profit
            |--------------------------------------------------------------------------
            */

            $profit =
                $totalRevenue
                - $totalExpenses;

            return [
                'crop_id' => $crop->id,

                'crop_name' => $crop->crop_name,

                'total_revenue' =>
                    round($totalRevenue, 2),

                'crop_expenses' =>
                    round($cropExpenses, 2),

                'post_harvest_loss_amount' =>
                    round($postHarvestLossAmount, 2),

                'profit' =>
                    round($profit, 2),

                'profit_status' =>
                    $profit > 0
                        ? 'profit'
                        : ($profit < 0
                            ? 'loss'
                            : 'break_even'),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Total Crop Profit
        |--------------------------------------------------------------------------
        */

        $totalCropProfit = $cropSummaries
            ->sum('profit');

        /*
        |--------------------------------------------------------------------------
        | Farm-Wide Expenses
        |--------------------------------------------------------------------------
        |
        | These are expenses where crop_id is NULL.
        |
        | NOTE:
        | At this stage, we filter farm-wide expenses by season
        | through the request date range if provided.
        */

        $farmWideExpensesQuery = Expense::whereNull('crop_id');

        /*
        |--------------------------------------------------------------------------
        | Optional Date Filtering
        |--------------------------------------------------------------------------
        */

        if ($request->filled('start_date')) {
            $farmWideExpensesQuery->whereDate(
                'expense_date',
                '>=',
                $request->start_date
            );
        }

        if ($request->filled('end_date')) {
            $farmWideExpensesQuery->whereDate(
                'expense_date',
                '<=',
                $request->end_date
            );
        }

        $farmWideExpenses = (float) $farmWideExpensesQuery
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Season Profit
        |--------------------------------------------------------------------------
        */

        $seasonProfit =
            $totalCropProfit
            - $farmWideExpenses;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Season profit analysis retrieved successfully.',

            'data' => [

                'season' => $season,

                'crop_count' => $crops->count(),

                'crops' => $cropSummaries,

                'financials' => [

                    'total_crop_profit' =>
                        round(
                            (float) $totalCropProfit,
                            2
                        ),

                    'farm_wide_expenses' =>
                        round(
                            $farmWideExpenses,
                            2
                        ),

                    'season_profit' =>
                        round(
                            (float) $seasonProfit,
                            2
                        ),

                    'profit_status' =>
                        $seasonProfit > 0
                            ? 'profit'
                            : ($seasonProfit < 0
                                ? 'loss'
                                : 'break_even'),
                ],
            ],
        ]);
    }
}