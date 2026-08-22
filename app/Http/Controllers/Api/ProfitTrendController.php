<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Models\Sale;
use App\Services\DashboardFilterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitTrendController extends Controller
{
    public function __construct(
        protected DashboardFilterService $filterService
    ) {}

    /**
     * Display monthly profit trend data.
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
        |
        | Default: Last 12 months including current month.
        |
        */
        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfMonth();

        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : $endDate
                ->copy()
                ->subMonths(11)
                ->startOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Filter Crops
        |--------------------------------------------------------------------------
        */
        $cropIds = $this->filterService->getFilteredCropIds($filters);

        /*
        |--------------------------------------------------------------------------
        | Build Monthly Trend
        |--------------------------------------------------------------------------
        */
        $trend = [];

        $currentMonth = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($currentMonth->lte($lastMonth)) {
            $monthStart = $currentMonth
                ->copy()
                ->startOfMonth();

            $monthEnd = $currentMonth
                ->copy()
                ->endOfMonth();

            /*
            |--------------------------------------------------------------------------
            | Revenue
            |--------------------------------------------------------------------------
            */
            $sales = Sale::query()
                ->whereBetween(
                    'sale_date',
                    [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ]
                )
                ->whereHas(
                    'harvest.crop',
                    function ($query) use ($cropIds) {
                        $query->whereIn(
                            'id',
                            $cropIds
                        );
                    }
                )
                ->get();

            $revenue = $sales->sum(
                function ($sale) {
                    return
                        (float) $sale->quantity_sold
                        * (float) $sale->price_per_unit;
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Crop Expenses
            |--------------------------------------------------------------------------
            |
            | Exclude "Post-Harvest Loss" expenses because loss amounts
            | are calculated directly from post_harvest_losses.
            |
            */
            $expenseQuery = Expense::query()
                ->where(
                    'category',
                    '!=',
                    'Post-Harvest Loss'
                )
                ->whereBetween(
                    'expense_date',
                    [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ]
                );

            $this->filterService->applyExpenseFilters(
                $expenseQuery,
                $filters,
                $cropIds
            );

            $cropExpenses = (float) $expenseQuery->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | Post-Harvest Loss Amount
            |--------------------------------------------------------------------------
            */
            $postHarvestLossAmount = (float) PostHarvestLoss::query()
                ->whereBetween(
                    'loss_date',
                    [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ]
                )
                ->whereHas(
                    'harvest.crop',
                    function ($query) use ($cropIds) {
                        $query->whereIn(
                            'id',
                            $cropIds
                        );
                    }
                )
                ->sum('loss_amount');

            /*
            |--------------------------------------------------------------------------
            | Total Expenses and Profit
            |--------------------------------------------------------------------------
            */
            $totalExpenses =
                (float) $cropExpenses
                + (float) $postHarvestLossAmount;

            $profit =
                (float) $revenue
                - $totalExpenses;

            $trend[] = [
                'month' =>
                    $currentMonth->format('Y-m'),

                'label' =>
                    $currentMonth->format('M Y'),

                'revenue' =>
                    round((float) $revenue, 2),

                'crop_expenses' =>
                    round((float) $cropExpenses, 2),

                'post_harvest_loss_amount' =>
                    round(
                        (float) $postHarvestLossAmount,
                        2
                    ),

                'total_expenses' =>
                    round($totalExpenses, 2),

                'profit' =>
                    round($profit, 2),

                'profit_status' =>
                    $profit > 0
                        ? 'profit'
                        : (
                            $profit < 0
                                ? 'loss'
                                : 'break_even'
                        ),
            ];

            $currentMonth->addMonth();
        }

        /*
        |--------------------------------------------------------------------------
        | Return Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'message' =>
                'Profit trend retrieved successfully.',
            'data' => [
                'filters' => [
                    'farm_id' => $filters['farm_id'],
                    'plot_id' => $filters['plot_id'],
                    'crop_name' => $filters['crop_name'],
                    'season' => $filters['season'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'period' => [
                    'start_date' =>
                        $startDate->toDateString(),
                    'end_date' =>
                        $endDate->toDateString(),
                ],
                'trend' => $trend,
            ],
        ]);
    }
}