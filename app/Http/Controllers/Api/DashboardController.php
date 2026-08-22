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

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardFilterService $filterService
    ) {}

    /**
     * Display dashboard KPI summary with optional filters.
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
        | Filtered Crop IDs
        |--------------------------------------------------------------------------
        */
        $cropIds = $this->filterService->getFilteredCropIds($filters);

        /*
        |--------------------------------------------------------------------------
        | Active Crop Plans
        |--------------------------------------------------------------------------
        */
        $activeCropCount = Crop::query()
            ->whereIn('id', $cropIds)
            ->whereIn('status', [
                'planned',
                'active',
                'growing',
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Regular Expenses
        |--------------------------------------------------------------------------
        |
        | Post-harvest loss expenses are excluded here because they are
        | calculated separately from the post_harvest_losses table.
        |
        */
        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('category', '!=', 'Post-Harvest Loss');

        $this->filterService->applyExpenseFilters(
            $expenseQuery,
            $filters,
            $cropIds
        );

        $regularExpenses = (float) $expenseQuery->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Post-Harvest Loss Amount
        |--------------------------------------------------------------------------
        |
        | Calculate directly from post_harvest_losses.
        | This prevents double-counting loss amounts that may also exist
        | as expense records.
        |
        */
        $postHarvestLossQuery = PostHarvestLoss::query()
            ->whereBetween('loss_date', [$startDate, $endDate])
            ->whereHas('harvest.crop', function ($query) use ($cropIds) {
                $query->whereIn('id', $cropIds);
            });

        $postHarvestLossAmount = (float) $postHarvestLossQuery->sum('loss_amount');

        /*
        |--------------------------------------------------------------------------
        | Total Expenses
        |--------------------------------------------------------------------------
        */
        $totalExpenses = $regularExpenses + $postHarvestLossAmount;

        /*
        |--------------------------------------------------------------------------
        | Total Revenue
        |--------------------------------------------------------------------------
        */
        $sales = Sale::query()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->whereHas('harvest.crop', function ($query) use ($cropIds) {
                $query->whereIn('id', $cropIds);
            })
            ->get();

        $totalRevenue = (float) $sales->sum(function ($sale) {
            return (float) $sale->quantity_sold * (float) $sale->price_per_unit;
        });

        /*
        |--------------------------------------------------------------------------
        | Net Profit / Loss
        |--------------------------------------------------------------------------
        */
        $netProfitLoss = $totalRevenue - $totalExpenses;

        $profitStatus = $netProfitLoss > 0
            ? 'profit'
            : (
                $netProfitLoss < 0
                    ? 'loss'
                    : 'break_even'
            );

        /*
        |--------------------------------------------------------------------------
        | Pending Notifications
        |--------------------------------------------------------------------------
        */
        $pendingNotificationCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => [
                'filters' => [
                    'farm_id' => $filters['farm_id'],
                    'plot_id' => $filters['plot_id'],
                    'crop_name' => $filters['crop_name'],
                    'season' => $filters['season'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'kpis' => [
                    'active_crop_plans' => (int) $activeCropCount,
                    'regular_expenses' => round((float) $regularExpenses, 2),
                    'post_harvest_loss_amount' => round((float) $postHarvestLossAmount, 2),
                    'total_expenses' => round((float) $totalExpenses, 2),
                    'total_revenue' => round((float) $totalRevenue, 2),
                    'net_profit_loss' => round((float) $netProfitLoss, 2),
                    'profit_status' => $profitStatus,
                    'pending_notification_count' => $pendingNotificationCount,
                ],
            ],
        ]);
    }
}