<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;

class CropFinancialSummaryController extends Controller
{
    /**
     * Display the financial and inventory summary for a crop.
     */
    public function show(Crop $crop)
    {
        $harvests = $crop->harvests()
            ->with([
                'sales',
                'postHarvestLosses',
            ])
            ->get();

        $totalHarvested = $harvests->sum(
            'quantity_harvested'
        );

        $totalSold = $harvests
            ->flatMap(function ($harvest) {
                return $harvest->sales;
            })
            ->sum('quantity_sold');

        $totalPostHarvestLoss = $harvests
            ->flatMap(function ($harvest) {
                return $harvest->postHarvestLosses;
            })
            ->sum('quantity_lost');

        $unsoldQuantity =
            $totalHarvested
            - $totalSold
            - $totalPostHarvestLoss;

        $totalRevenue = $harvests
            ->flatMap(function ($harvest) {
                return $harvest->sales;
            })
            ->sum(function ($sale) {
                return
                    $sale->quantity_sold
                    * $sale->price_per_unit;
            });

        $totalExpenses = $crop
            ->expenses()
            ->sum('amount');

        $profit = $totalRevenue - $totalExpenses;

        return response()->json([
            'success' => true,
            'message' => 'Crop financial summary retrieved successfully.',

            'data' => [
                'crop_id' => $crop->id,
                'crop_name' => $crop->crop_name,

                'inventory' => [
                    'total_harvested' => (float) $totalHarvested,
                    'total_sold' => (float) $totalSold,
                    'total_post_harvest_loss' =>
                        (float) $totalPostHarvestLoss,
                    'unsold_quantity' =>
                        (float) $unsoldQuantity,
                ],

                'financials' => [
                    'total_revenue' =>
                        (float) $totalRevenue,

                    'total_expenses' =>
                        (float) $totalExpenses,

                    'profit' =>
                        (float) $profit,
                ],
            ],
        ]);
    }
}