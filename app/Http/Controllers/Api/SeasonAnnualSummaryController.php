<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Models\Sale;
use Illuminate\Http\Request;

class SeasonAnnualSummaryController extends Controller
{
    /**
     * FR-07.8
     * Get summary report for a season.
     *
     * Example:
     * GET /api/dashboard/summary/season?season=Maha
     */
    public function season(Request $request)
    {
        $validated = $request->validate([
            'season' => [
                'required',
                'string',
                'max:255',
            ],

            'farm_id' => [
                'nullable',
                'integer',
                'exists:farms,id',
            ],
        ]);

        $cropQuery = Crop::query()
            ->where(
                'season',
                $validated['season']
            );

        if (!empty($validated['farm_id'])) {
            $cropQuery->whereHas(
                'plot',
                function ($query) use ($validated) {
                    $query->where(
                        'farm_id',
                        $validated['farm_id']
                    );
                }
            );
        }

        $crops = $cropQuery->get();

        return response()->json([
            'success' => true,

            'message' =>
                'Season summary report retrieved successfully.',

            'data' => $this->buildSummary(
                $crops,
                [
                    'type' => 'season',
                    'season' => $validated['season'],
                    'farm_id' =>
                        $validated['farm_id'] ?? null,
                ]
            ),
        ]);
    }

    /**
     * FR-07.8
     * Get annual summary report.
     *
     * Example:
     * GET /api/dashboard/summary/annual/2026
     */
    public function annual(
        Request $request,
        int $year
    ) {
        $validated = $request->validate([
            'farm_id' => [
                'nullable',
                'integer',
                'exists:farms,id',
            ],
        ]);

        $cropQuery = Crop::query()
            ->whereYear(
                'planting_date',
                $year
            );

        if (!empty($validated['farm_id'])) {
            $cropQuery->whereHas(
                'plot',
                function ($query) use ($validated) {
                    $query->where(
                        'farm_id',
                        $validated['farm_id']
                    );
                }
            );
        }

        $crops = $cropQuery->get();

        return response()->json([
            'success' => true,

            'message' =>
                'Annual summary report retrieved successfully.',

            'data' => $this->buildSummary(
                $crops,
                [
                    'type' => 'annual',
                    'year' => $year,
                    'farm_id' =>
                        $validated['farm_id'] ?? null,
                ]
            ),
        ]);
    }

    /**
     * Build financial summary.
     */
    private function buildSummary(
        $crops,
        array $reportDetails
    ): array {
        $cropIds = $crops->pluck('id');

        $cropPerformance = $crops
            ->map(function ($crop) {

                /*
                |--------------------------------------------------------------------------
                | Revenue
                |--------------------------------------------------------------------------
                */

                $revenue = Sale::query()
                    ->whereHas(
                        'harvest',
                        function ($query) use ($crop) {
                            $query->where(
                                'crop_id',
                                $crop->id
                            );
                        }
                    )
                    ->get()
                    ->sum(function ($sale) {
                        return
                            (float) $sale->quantity_sold
                            * (float) $sale->price_per_unit;
                    });

                /*
                |--------------------------------------------------------------------------
                | Regular Crop Expenses
                |--------------------------------------------------------------------------
                */

                $cropExpenses = Expense::query()
                    ->where(
                        'crop_id',
                        $crop->id
                    )
                    ->where(
                        'category',
                        '!=',
                        'Post-Harvest Loss'
                    )
                    ->sum('amount');

                /*
                |--------------------------------------------------------------------------
                | Post-Harvest Losses
                |--------------------------------------------------------------------------
                */

                $postHarvestLoss = PostHarvestLoss::query()
                    ->whereHas(
                        'harvest',
                        function ($query) use ($crop) {
                            $query->where(
                                'crop_id',
                                $crop->id
                            );
                        }
                    )
                    ->sum('loss_amount');

                /*
                |--------------------------------------------------------------------------
                | Total Expenses
                |--------------------------------------------------------------------------
                */

                $totalExpenses =
                    (float) $cropExpenses
                    + (float) $postHarvestLoss;

                /*
                |--------------------------------------------------------------------------
                | Profit
                |--------------------------------------------------------------------------
                */

                $profit =
                    (float) $revenue
                    - (float) $totalExpenses;

                return [
                    'crop_id' =>
                        $crop->id,

                    'crop_name' =>
                        $crop->crop_name,

                    'season' =>
                        $crop->season,

                    'total_revenue' =>
                        round((float) $revenue, 2),

                    'total_expenses' =>
                        round((float) $totalExpenses, 2),

                    'net_profit' =>
                        round((float) $profit, 2),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Overall Totals
        |--------------------------------------------------------------------------
        */

        $totalRevenue =
            $cropPerformance->sum(
                'total_revenue'
            );

        $totalExpenses =
            $cropPerformance->sum(
                'total_expenses'
            );

        $netProfit =
            $totalRevenue
            - $totalExpenses;

        /*
        |--------------------------------------------------------------------------
        | Best / Worst Crop
        |--------------------------------------------------------------------------
        */

        $bestCrop =
            $cropPerformance
                ->sortByDesc('net_profit')
                ->first();

        $worstCrop =
            $cropPerformance
                ->sortBy('net_profit')
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Profit Status
        |--------------------------------------------------------------------------
        */

        $profitStatus =
            $netProfit > 0
                ? 'profit'
                : (
                    $netProfit < 0
                        ? 'loss'
                        : 'break_even'
                );

        return [

            'report' =>
                $reportDetails,

            'summary' => [

                'total_crops' =>
                    $crops->count(),

                'total_revenue' =>
                    round(
                        (float) $totalRevenue,
                        2
                    ),

                'total_expenses' =>
                    round(
                        (float) $totalExpenses,
                        2
                    ),

                'net_profit_loss' =>
                    round(
                        (float) $netProfit,
                        2
                    ),

                'profit_status' =>
                    $profitStatus,
            ],

            'best_performing_crop' =>
                $bestCrop,

            'worst_performing_crop' =>
                $worstCrop,

            'crop_performance' =>
                $cropPerformance,
        ];
    }
}