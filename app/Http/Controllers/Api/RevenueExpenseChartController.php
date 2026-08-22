<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Models\Sale;
use App\Services\DashboardFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueExpenseChartController extends Controller
{
    public function __construct(
        protected DashboardFilterService $filterService
    ) {}

    /**
     * Display revenue vs expenses data per crop.
     *
     * Supported filters:
     * - farm_id
     * - plot_id
     * - crop_name
     * - season
     * - start_date
     * - end_date
     */
    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Filters
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate($this->filterService->validationRules());
        $filters = $this->filterService->getFilters($validated);

        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        */
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        /*
        |--------------------------------------------------------------------------
        | Filter Crops
        |--------------------------------------------------------------------------
        */
        $cropQuery = Crop::query();
        $this->filterService->applyCropFilters($cropQuery, $filters);
        $crops = $cropQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Build Chart Data
        |--------------------------------------------------------------------------
        */
        $chartData = $crops->map(
            function ($crop) use ($startDate, $endDate) {

                /*
                |--------------------------------------------------------------------------
                | Revenue
                |--------------------------------------------------------------------------
                */
                $salesQuery = Sale::query()
                    ->whereHas(
                        'harvest',
                        function ($harvestQuery) use ($crop) {
                            $harvestQuery->where(
                                'crop_id',
                                $crop->id
                            );
                        }
                    );

                if ($startDate && $endDate) {
                    $salesQuery->whereBetween(
                        'sale_date',
                        [$startDate, $endDate]
                    );
                } elseif ($startDate) {
                    $salesQuery->where('sale_date', '>=', $startDate);
                } elseif ($endDate) {
                    $salesQuery->where('sale_date', '<=', $endDate);
                }

                $totalRevenue = (float) $salesQuery
                    ->get()
                    ->sum(function ($sale) {
                        return
                            (float) $sale->quantity_sold
                            * (float) $sale->price_per_unit;
                    });

                /*
                |--------------------------------------------------------------------------
                | Crop Expenses
                |--------------------------------------------------------------------------
                |
                | Exclude "Post-Harvest Loss" to prevent double-counting.
                |
                */
                $expensesQuery = Expense::query()
                    ->where(
                        'crop_id',
                        $crop->id
                    )
                    ->where(
                        'category',
                        '!=',
                        'Post-Harvest Loss'
                    );

                if ($startDate && $endDate) {
                    $expensesQuery->whereBetween(
                        'expense_date',
                        [$startDate, $endDate]
                    );
                } elseif ($startDate) {
                    $expensesQuery->where('expense_date', '>=', $startDate);
                } elseif ($endDate) {
                    $expensesQuery->where('expense_date', '<=', $endDate);
                }

                $cropExpenses = (float) $expensesQuery
                    ->sum('amount');

                /*
                |--------------------------------------------------------------------------
                | Post-Harvest Loss Amount
                |--------------------------------------------------------------------------
                */
                $lossQuery = PostHarvestLoss::query()
                    ->whereHas(
                        'harvest',
                        function ($harvestQuery) use ($crop) {
                            $harvestQuery->where(
                                'crop_id',
                                $crop->id
                            );
                        }
                    );

                if ($startDate && $endDate) {
                    $lossQuery->whereBetween(
                        'loss_date',
                        [$startDate, $endDate]
                    );
                } elseif ($startDate) {
                    $lossQuery->where('loss_date', '>=', $startDate);
                } elseif ($endDate) {
                    $lossQuery->where('loss_date', '<=', $endDate);
                }

                $cropLossAmount = (float) $lossQuery
                    ->sum('loss_amount');

                /*
                |--------------------------------------------------------------------------
                | Total Expenses
                |--------------------------------------------------------------------------
                */
                $totalExpenses = $cropExpenses + $cropLossAmount;

                /*
                |--------------------------------------------------------------------------
                | Return Crop Chart Data
                |--------------------------------------------------------------------------
                */
                return [
                    'crop_id' => $crop->id,

                    'crop_name' => $crop->crop_name,

                    'season' => $crop->season,

                    'total_revenue' =>
                        round((float) $totalRevenue, 2),

                    'total_expenses' =>
                        round((float) $totalExpenses, 2),
                ];
            }
        )->values();

        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */
        $totalRevenue = (float) $chartData->sum('total_revenue');

        $totalExpenses = (float) $chartData->sum('total_expenses');

        return response()->json([
            'success' => true,

            'message' =>
                'Revenue vs expenses chart data retrieved successfully.',

            'data' => [

                'filters' => [
                    'farm_id' =>
                        $filters['farm_id'],

                    'plot_id' =>
                        $filters['plot_id'],

                    'crop_name' =>
                        $filters['crop_name'],

                    'season' =>
                        $filters['season'],

                    'start_date' =>
                        $startDate,

                    'end_date' =>
                        $endDate,
                ],

                'chart_data' =>
                    $chartData,

                'summary' => [

                    'total_revenue' =>
                        round($totalRevenue, 2),

                    'total_expenses' =>
                        round($totalExpenses, 2),
                ],
            ],
        ]);
    }
}
