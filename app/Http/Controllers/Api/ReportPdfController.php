<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportPdfController extends Controller
{
    /**
     * FR-10.4
     *
     * Generate Crop Profit Report PDF.
     */
    public function cropProfit(Request $request, Crop $crop)
    {
        $chartImage = $request->input('chart_image');

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        $expenses = Expense::query()
            ->where('crop_id', $crop->id)
            ->where('category', '!=', 'Post-Harvest Loss')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Revenue
        |--------------------------------------------------------------------------
        */

        $sales = Sale::query()
            ->whereHas('harvest', function ($query) use ($crop) {
                $query->where('crop_id', $crop->id);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Post Harvest Loss
        |--------------------------------------------------------------------------
        */

        $postHarvestLoss = PostHarvestLoss::query()
            ->whereHas('harvest', function ($query) use ($crop) {
                $query->where('crop_id', $crop->id);
            })
            ->sum('loss_amount');

        /*
        |--------------------------------------------------------------------------
        | Financial Calculations
        |--------------------------------------------------------------------------
        */

        $totalRevenue = $sales->sum(function ($sale) {
            return (float) $sale->quantity_sold
                * (float) $sale->price_per_unit;
        });

        $totalExpenses = $expenses->sum('amount');

        $totalCost =
            (float) $totalExpenses
            + (float) $postHarvestLoss;

        $profit =
            (float) $totalRevenue
            - (float) $totalCost;

        $profitStatus =
            $profit > 0
                ? 'profit'
                : (
                    $profit < 0
                        ? 'loss'
                        : 'break_even'
                );

        /*
        |--------------------------------------------------------------------------
        | Report Data
        |--------------------------------------------------------------------------
        */

        $report = [
            'crop' => [
                'id' => $crop->id,
                'crop_name' => $crop->crop_name,
                'variety' => $crop->variety,
                'season' => $crop->season,
                'planting_date' => $crop->planting_date,
                'expected_harvest_date' =>
                    $crop->expected_harvest_date,
                'status' => $crop->status,
            ],

            'expenses' => $expenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'date' => $expense->expense_date,
                    'category' => $expense->category,
                    'description' => $expense->description,
                    'amount' => (float) $expense->amount,
                ];
            })->values()->toArray(),

            'revenue_entries' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'buyer_name' => $sale->buyer_name,
                    'sale_date' => $sale->sale_date,
                    'quantity_sold' =>
                        (float) $sale->quantity_sold,
                    'price_per_unit' =>
                        (float) $sale->price_per_unit,
                    'amount' =>
                        (float) $sale->quantity_sold
                        * (float) $sale->price_per_unit,
                    'payment_status' =>
                        $sale->payment_status,
                ];
            })->values()->toArray(),

            'financial_summary' => [
                'total_revenue' =>
                    round($totalRevenue, 2),

                'total_expenses' =>
                    round((float) $totalExpenses, 2),

                'post_harvest_loss_amount' =>
                    round((float) $postHarvestLoss, 2),

                'total_cost' =>
                    round($totalCost, 2),

                'profit' =>
                    round($profit, 2),

                'profit_status' =>
                    $profitStatus,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'reports.crop-profit-pdf',
            [
                'report' => $report,
                'chartImage' => $chartImage,
            ]
        );

        return $pdf->download(
            'crop-profit-report-' . $crop->id . '.pdf'
        );
    }
}