<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;

class BreakEvenAnalysisController extends Controller
{
    /**
     * Display break-even analysis for a crop plan.
     */
    public function show(Crop $crop)
    {
        $harvests = $crop->harvests()
            ->with([
                'sales',
                'postHarvestLosses',
            ])
            ->get();

        // Current revenue from all sales.
        $currentRevenue = $harvests
            ->flatMap(function ($harvest) {
                return $harvest->sales;
            })
            ->sum(function ($sale) {
                return
                    (float) $sale->quantity_sold
                    * (float) $sale->price_per_unit;
            });

        // Crop-specific expenses only.
        $cropExpenses = (float) $crop
            ->expenses()
            ->sum('amount');

        // Financial value of post-harvest losses.
        $postHarvestLossAmount = (float) $harvests
            ->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            })
            ->sum('loss_amount');

        // Total expenses incurred to date.
        $totalExpensesIncurred =
            $cropExpenses
            + $postHarvestLossAmount;

        // Revenue still required to reach break-even.
        $remainingRevenueRequired = max(
            0,
            $totalExpensesIncurred - $currentRevenue
        );

        // Progress toward the break-even point.
        $breakEvenProgressPercentage =
            $totalExpensesIncurred > 0
                ? ($currentRevenue / $totalExpensesIncurred) * 100
                : 0;

        // Current financial position.
        $currentProfit =
            $currentRevenue
            - $totalExpensesIncurred;

        $breakEvenStatus = $currentRevenue >= $totalExpensesIncurred
            ? 'break_even_reached'
            : 'below_break_even';

        return response()->json([
            'success' => true,
            'message' =>
                'Break-even analysis retrieved successfully.',

            'data' => [
                'crop_id' => $crop->id,
                'crop_name' => $crop->crop_name,
                'crop_status' => $crop->status,

                'analysis' => [
                    'current_revenue' =>
                        round($currentRevenue, 2),

                    'crop_expenses' =>
                        round($cropExpenses, 2),

                    'post_harvest_loss_amount' =>
                        round($postHarvestLossAmount, 2),

                    'total_expenses_incurred' =>
                        round($totalExpensesIncurred, 2),

                    'minimum_revenue_required_for_break_even' =>
                        round($totalExpensesIncurred, 2),

                    'remaining_revenue_required' =>
                        round($remainingRevenueRequired, 2),

                    'current_profit' =>
                        round($currentProfit, 2),

                    'break_even_progress_percentage' =>
                        round($breakEvenProgressPercentage, 2),

                    'break_even_status' =>
                        $breakEvenStatus,
                ],
            ],
        ]);
    }
}