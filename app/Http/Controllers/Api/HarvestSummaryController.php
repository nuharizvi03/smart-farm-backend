<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;

class HarvestSummaryController extends Controller
{
    /**
     * Display harvest, sales, inventory and revenue summary for a crop.
     */
    public function show(Crop $crop)
    {
        $totalHarvested = $crop->harvests()
            ->sum('quantity_harvested');

        $totalSold = $crop->harvests()
            ->withSum('sales', 'quantity_sold')
            ->get()
            ->sum('sales_sum_quantity_sold');

        $totalRevenue = $crop->harvests()
            ->with('sales')
            ->get()
            ->sum(function ($harvest) {
                return $harvest->sales->sum(function ($sale) {
                    return $sale->quantity_sold * $sale->price_per_unit;
                });
            });

        $unsoldQuantity = $totalHarvested - $totalSold;

        return response()->json([
            'success' => true,
            'message' => 'Crop harvest summary retrieved successfully.',
            'data' => [
                'crop_id' => $crop->id,
                'total_harvested' => number_format(
                    $totalHarvested,
                    2,
                    '.',
                    ''
                ),
                'total_sold' => number_format(
                    $totalSold,
                    2,
                    '.',
                    ''
                ),
                'unsold_quantity' => number_format(
                    max(0, $unsoldQuantity),
                    2,
                    '.',
                    ''
                ),
                'total_revenue' => number_format(
                    $totalRevenue,
                    2,
                    '.',
                    ''
                ),
            ],
        ]);
    }
}