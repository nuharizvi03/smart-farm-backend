<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Services\DashboardFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CropPerformanceController extends Controller
{
    public function __construct(
        protected DashboardFilterService $filterService
    ) {}

    /**
     * Display crop performance comparison data.
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
        $startDate = $filters['start_date']
            ?? now()->subMonths(11)->startOfMonth()->toDateString();

        $endDate = $filters['end_date']
            ?? now()->endOfMonth()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Crop Query
        |--------------------------------------------------------------------------
        */
        $cropQuery = Crop::query();

        $this->filterService->applyCropFilters(
            $cropQuery,
            $filters
        );

        $cropQuery
            ->with([
                'harvests.sales',
                'harvests.postHarvestLosses',
                'expenses',
            ])
            ->whereBetween(
                'planting_date',
                [$startDate, $endDate]
            );

        $crops = $cropQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Group Crop Plans by Crop Type
        |--------------------------------------------------------------------------
        */
        $cropTypes = $crops
            ->groupBy('crop_name')
            ->map(function ($cropGroup, $cropName) {

                $totalYield = 0;
                $totalRevenue = 0;
                $totalCropExpenses = 0;
                $totalPostHarvestLossAmount = 0;

                foreach ($cropGroup as $crop) {

                    /*
                    |--------------------------------------------------------------------------
                    | Total Yield
                    |--------------------------------------------------------------------------
                    */
                    $cropYield = (float) $crop
                        ->harvests
                        ->sum('quantity_harvested');

                    $totalYield += $cropYield;

                    /*
                    |--------------------------------------------------------------------------
                    | Revenue
                    |--------------------------------------------------------------------------
                    */
                    $cropRevenue = (float) $crop
                        ->harvests
                        ->flatMap(function ($harvest) {
                            return $harvest->sales;
                        })
                        ->sum(function ($sale) {
                            return
                                (float) $sale->quantity_sold
                                * (float) $sale->price_per_unit;
                        });

                    $totalRevenue += $cropRevenue;

                    /*
                    |--------------------------------------------------------------------------
                    | Crop-Specific Expenses
                    |--------------------------------------------------------------------------
                    */
                    $cropExpenses = (float) $crop
                        ->expenses
                        ->where(
                            'category',
                            '!=',
                            'Post-Harvest Loss'
                        )
                        ->sum('amount');

                    $totalCropExpenses += $cropExpenses;

                    /*
                    |--------------------------------------------------------------------------
                    | Post-Harvest Loss Amount
                    |--------------------------------------------------------------------------
                    */
                    $postHarvestLossAmount = (float) $crop
                        ->harvests
                        ->flatMap(function ($harvest) {
                            return $harvest->postHarvestLosses;
                        })
                        ->sum('loss_amount');

                    $totalPostHarvestLossAmount += $postHarvestLossAmount;
                }

                /*
                |--------------------------------------------------------------------------
                | Profit
                |--------------------------------------------------------------------------
                */
                $totalExpenses =
                    $totalCropExpenses
                    + $totalPostHarvestLossAmount;

                $profit =
                    $totalRevenue
                    - $totalExpenses;

                $profitMarginPercentage =
                    $totalRevenue > 0
                        ? round(
                            (
                                $profit
                                / $totalRevenue
                            ) * 100,
                            2
                        )
                        : 0;

                return [
                    'crop_name' => $cropName,

                    'crop_plan_count' =>
                        $cropGroup->count(),

                    'total_yield' =>
                        round(
                            $totalYield,
                            2
                        ),

                    'total_revenue' =>
                        round(
                            $totalRevenue,
                            2
                        ),

                    'total_expenses' =>
                        round(
                            $totalExpenses,
                            2
                        ),

                    'profit' =>
                        round(
                            $profit,
                            2
                        ),

                    'profit_margin_percentage' =>
                        $profitMarginPercentage,

                    'profit_status' =>
                        $profit > 0
                            ? 'profit'
                            : (
                                $profit < 0
                                    ? 'loss'
                                    : 'break_even'
                            ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Chart Data
        |--------------------------------------------------------------------------
        */
        $labels = $cropTypes
            ->pluck('crop_name')
            ->values();

        $yieldData = $cropTypes
            ->pluck('total_yield')
            ->values();

        $profitData = $cropTypes
            ->pluck('profit')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,

            'message' =>
                'Crop performance comparison retrieved successfully.',

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

                'crop_types' =>
                    $cropTypes,

                'chart' => [

                    'labels' =>
                        $labels,

                    'datasets' => [

                        [
                            'label' =>
                                'Total Yield (kg)',

                            'data' =>
                                $yieldData,
                        ],

                        [
                            'label' =>
                                'Profit',

                            'data' =>
                                $profitData,
                        ],
                    ],
                ],
            ],
        ]);
    }
}