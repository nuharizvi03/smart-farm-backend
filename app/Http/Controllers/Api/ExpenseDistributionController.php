<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Services\DashboardFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseDistributionController extends Controller
{
    public function __construct(
        protected DashboardFilterService $filterService
    ) {}

    /**
     * Display expense distribution grouped by category.
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
            ?? now()->startOfMonth()->toDateString();

        $endDate = $filters['end_date']
            ?? now()->endOfMonth()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Filter Crops
        |--------------------------------------------------------------------------
        */
        $cropIds = $this->filterService->getFilteredCropIds($filters);

        /*
        |--------------------------------------------------------------------------
        | Regular Expense Distribution
        |--------------------------------------------------------------------------
        |
        | Post-Harvest Loss is excluded because it is calculated directly
        | from the post_harvest_losses table.
        |
        */
        $expenseQuery = Expense::query()
            ->whereBetween(
                'expense_date',
                [$startDate, $endDate]
            )
            ->where(
                'category',
                '!=',
                'Post-Harvest Loss'
            );

        $this->filterService->applyExpenseFilters(
            $expenseQuery,
            $filters,
            $cropIds
        );

        $regularExpenses = $expenseQuery
            ->selectRaw(
                'category, SUM(amount) as total_amount'
            )
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Post-Harvest Loss Distribution
        |--------------------------------------------------------------------------
        */
        $postHarvestLossAmount = (float) PostHarvestLoss::query()
            ->whereBetween(
                'loss_date',
                [$startDate, $endDate]
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
        | Build Distribution
        |--------------------------------------------------------------------------
        */
        $distribution = $regularExpenses
            ->map(function ($expense) {
                return [
                    'category' => $expense->category,
                    'amount' => round(
                        (float) $expense->total_amount,
                        2
                    ),
                ];
            })
            ->values();

        /*
        | Add Post-Harvest Loss as its own category when it exists.
        */
        if ((float) $postHarvestLossAmount > 0) {
            $distribution->push([
                'category' => 'Post-Harvest Loss',
                'amount' => round(
                    (float) $postHarvestLossAmount,
                    2
                ),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Total
        |--------------------------------------------------------------------------
        */
        $totalExpenses = (float) $distribution->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Add Percentage
        |--------------------------------------------------------------------------
        */
        $distribution = $distribution
            ->map(function ($item) use ($totalExpenses) {
                $percentage =
                    $totalExpenses > 0
                        ? (
                            $item['amount']
                            / $totalExpenses
                        ) * 100
                        : 0;

                return [
                    'category' => $item['category'],
                    'amount' => round(
                        (float) $item['amount'],
                        2
                    ),
                    'percentage' => round(
                        $percentage,
                        2
                    ),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'message' =>
                'Expense distribution retrieved successfully.',
            'data' => [
                'filters' => [
                    'farm_id' => $filters['farm_id'],
                    'plot_id' => $filters['plot_id'],
                    'crop_name' => $filters['crop_name'],
                    'season' => $filters['season'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'distribution' => $distribution,
                'total_expenses' =>
                    round(
                        (float) $totalExpenses,
                        2
                    ),
            ],
        ]);
    }
}