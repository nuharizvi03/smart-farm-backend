<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\PostHarvestLoss;
use App\Models\Sale;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {
    }

    /**
     * FR-10.1
     * Generate Crop Profit Report.
     */
    public function cropProfit(Crop $crop): JsonResponse
    {
        $report = $this->reportService
            ->cropProfit($crop);

        return response()->json([
            'success' => true,
            'message' =>
                'Crop profit report generated successfully.',
            'data' => $report,
        ]);
    }

    public function cropProfitCsv(Crop $crop)
{
    $report = $this->reportService->cropProfit($crop);

    $filename = 'crop-profit-report-' . $crop->id . '.csv';

    return response()->streamDownload(function () use ($report) {
        $handle = fopen('php://output', 'w');

        // Report title
        fputcsv($handle, ['Crop Profit Report']);
        fputcsv($handle, []);

        // Crop information
        fputcsv($handle, ['Crop Information']);
        fputcsv($handle, ['Crop ID', $report['crop']['id']]);
        fputcsv($handle, ['Crop Name', $report['crop']['crop_name']]);
        fputcsv($handle, ['Variety', $report['crop']['variety']]);
        fputcsv($handle, ['Season', $report['crop']['season']]);
        fputcsv($handle, ['Planting Date', $report['crop']['planting_date']]);
        fputcsv(
            $handle,
            [
                'Expected Harvest Date',
                $report['crop']['expected_harvest_date']
            ]
        );
        fputcsv($handle, ['Status', $report['crop']['status']]);
        fputcsv($handle, []);

        // Expenses
        fputcsv($handle, ['Expenses']);
        fputcsv(
            $handle,
            ['Date', 'Category', 'Description', 'Amount']
        );

        foreach ($report['expenses'] as $expense) {
            fputcsv($handle, [
                $expense['date'],
                $expense['category'],
                $expense['description'],
                $expense['amount'],
            ]);
        }

        fputcsv($handle, []);

        // Revenue
        fputcsv($handle, ['Revenue Entries']);
        fputcsv(
            $handle,
            [
                'Date',
                'Buyer',
                'Quantity Sold',
                'Price Per Unit',
                'Amount',
                'Payment Status'
            ]
        );

        foreach ($report['revenue_entries'] as $sale) {
            fputcsv($handle, [
                $sale['sale_date'],
                $sale['buyer_name'],
                $sale['quantity_sold'],
                $sale['price_per_unit'],
                $sale['amount'],
                $sale['payment_status'],
            ]);
        }

        fputcsv($handle, []);

        // Financial summary
        fputcsv($handle, ['Financial Summary']);
        fputcsv(
            $handle,
            ['Total Revenue', $report['financial_summary']['total_revenue']]
        );
        fputcsv(
            $handle,
            ['Total Expenses', $report['financial_summary']['total_expenses']]
        );
        fputcsv(
            $handle,
            [
                'Post Harvest Loss',
                $report['financial_summary']['post_harvest_loss_amount']
            ]
        );
        fputcsv(
            $handle,
            ['Total Cost', $report['financial_summary']['total_cost']]
        );
        fputcsv(
            $handle,
            ['Profit', $report['financial_summary']['profit']]
        );
        fputcsv(
            $handle,
            ['Profit Status', $report['financial_summary']['profit_status']]
        );

        fclose($handle);
    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}

public function cropProfitPdf(Request $request, Crop $crop)
{
    $request->validate([
        'chart_image' => [
            'nullable',
            'string',
        ],
    ]);

    $report = $this->reportService->cropProfit($crop);

    $chartImage = $request->input('chart_image');

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

/**
 * FR-10.5
 * Export seasonal financial report as CSV.
 */
public function seasonSummaryCsv(Request $request)
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

    // Generate the same report data used by the JSON endpoint
    $cropQuery = Crop::query()
        ->where('season', $validated['season']);

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

    $summary = $this->buildFinancialSummaryForCsv($crops);

    $filename = 'season-financial-report-' .
        str_replace(' ', '-', strtolower($validated['season'])) .
        '.csv';

    return response()->streamDownload(function () use (
        $summary,
        $validated
    ) {

        $handle = fopen('php://output', 'w');

        fputcsv($handle, ['Season Financial Report']);
        fputcsv($handle, []);

        fputcsv($handle, ['Report Information']);
        fputcsv($handle, ['Season', $validated['season']]);

        fputcsv(
            $handle,
            ['Farm ID', $validated['farm_id'] ?? 'All Farms']
        );

        fputcsv($handle, []);

        fputcsv($handle, ['Financial Summary']);

        fputcsv($handle, [
            'Total Crops',
            $summary['summary']['total_crops']
        ]);

        fputcsv($handle, [
            'Total Revenue',
            $summary['summary']['total_revenue']
        ]);

        fputcsv($handle, [
            'Total Expenses',
            $summary['summary']['total_expenses']
        ]);

        fputcsv($handle, [
            'Net Profit/Loss',
            $summary['summary']['net_profit_loss']
        ]);

        fputcsv($handle, [
            'Profit Status',
            $summary['summary']['profit_status']
        ]);

        fputcsv($handle, []);

        fputcsv($handle, ['Crop Performance']);

        fputcsv($handle, [
            'Crop ID',
            'Crop Name',
            'Season',
            'Total Revenue',
            'Total Expenses',
            'Net Profit'
        ]);

        foreach ($summary['crop_performance'] as $crop) {

            fputcsv($handle, [
                $crop['crop_id'],
                $crop['crop_name'],
                $crop['season'],
                $crop['total_revenue'],
                $crop['total_expenses'],
                $crop['net_profit'],
            ]);
        }

        fputcsv($handle, []);

        fputcsv($handle, ['Best Performing Crop']);

        if ($summary['best_performing_crop']) {
            fputcsv($handle, [
                'Crop ID',
                'Crop Name',
                'Net Profit'
            ]);

            fputcsv($handle, [
                $summary['best_performing_crop']['crop_id'],
                $summary['best_performing_crop']['crop_name'],
                $summary['best_performing_crop']['net_profit'],
            ]);
        }

        fputcsv($handle, []);

        fputcsv($handle, ['Worst Performing Crop']);

        if ($summary['worst_performing_crop']) {
            fputcsv($handle, [
                'Crop ID',
                'Crop Name',
                'Net Profit'
            ]);

            fputcsv($handle, [
                $summary['worst_performing_crop']['crop_id'],
                $summary['worst_performing_crop']['crop_name'],
                $summary['worst_performing_crop']['net_profit'],
            ]);
        }

        fclose($handle);

    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}


/**
 * FR-10.5
 * Export annual financial report as CSV.
 */
public function annualSummaryCsv(
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
        ->whereYear('planting_date', $year);

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

    $summary = $this->buildFinancialSummaryForCsv($crops);

    $filename =
        'annual-financial-report-' .
        $year .
        '.csv';

    return response()->streamDownload(function () use (
        $summary,
        $year,
        $validated
    ) {

        $handle = fopen('php://output', 'w');

        fputcsv($handle, ['Annual Financial Report']);
        fputcsv($handle, []);

        fputcsv($handle, ['Report Information']);

        fputcsv($handle, [
            'Year',
            $year
        ]);

        fputcsv($handle, [
            'Farm ID',
            $validated['farm_id'] ?? 'All Farms'
        ]);

        fputcsv($handle, []);

        fputcsv($handle, ['Financial Summary']);

        fputcsv($handle, [
            'Total Crops',
            $summary['summary']['total_crops']
        ]);

        fputcsv($handle, [
            'Total Revenue',
            $summary['summary']['total_revenue']
        ]);

        fputcsv($handle, [
            'Total Expenses',
            $summary['summary']['total_expenses']
        ]);

        fputcsv($handle, [
            'Net Profit/Loss',
            $summary['summary']['net_profit_loss']
        ]);

        fputcsv($handle, [
            'Profit Status',
            $summary['summary']['profit_status']
        ]);

        fputcsv($handle, []);

        fputcsv($handle, ['Crop Performance']);

        fputcsv($handle, [
            'Crop ID',
            'Crop Name',
            'Season',
            'Total Revenue',
            'Total Expenses',
            'Net Profit'
        ]);

        foreach ($summary['crop_performance'] as $crop) {

            fputcsv($handle, [
                $crop['crop_id'],
                $crop['crop_name'],
                $crop['season'],
                $crop['total_revenue'],
                $crop['total_expenses'],
                $crop['net_profit'],
            ]);
        }

        fputcsv($handle, []);

        fputcsv($handle, ['Best Performing Crop']);

        if ($summary['best_performing_crop']) {

            fputcsv($handle, [
                'Crop ID',
                'Crop Name',
                'Net Profit'
            ]);

            fputcsv($handle, [
                $summary['best_performing_crop']['crop_id'],
                $summary['best_performing_crop']['crop_name'],
                $summary['best_performing_crop']['net_profit'],
            ]);
        }

        fputcsv($handle, []);

        fputcsv($handle, ['Worst Performing Crop']);

        if ($summary['worst_performing_crop']) {

            fputcsv($handle, [
                'Crop ID',
                'Crop Name',
                'Net Profit'
            ]);

            fputcsv($handle, [
                $summary['worst_performing_crop']['crop_id'],
                $summary['worst_performing_crop']['crop_name'],
                $summary['worst_performing_crop']['net_profit'],
            ]);
        }

        fclose($handle);

    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}
private function buildFinancialSummaryForCsv($crops): array
{
    $cropPerformance = $crops
        ->map(function ($crop) {

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

            $totalExpenses =
                (float) $cropExpenses +
                (float) $postHarvestLoss;

            $profit =
                (float) $revenue -
                (float) $totalExpenses;

            return [
                'crop_id' =>
                    $crop->id,

                'crop_name' =>
                    $crop->crop_name,

                'season' =>
                    $crop->season,

                'total_revenue' =>
                    round($revenue, 2),

                'total_expenses' =>
                    round($totalExpenses, 2),

                'net_profit' =>
                    round($profit, 2),
            ];
        })
        ->values();

    $totalRevenue =
        $cropPerformance->sum('total_revenue');

    $totalExpenses =
        $cropPerformance->sum('total_expenses');

    $netProfit =
        $totalRevenue -
        $totalExpenses;

    $bestCrop =
        $cropPerformance
            ->sortByDesc('net_profit')
            ->first();

    $worstCrop =
        $cropPerformance
            ->sortBy('net_profit')
            ->first();

    $profitStatus =
        $netProfit > 0
            ? 'profit'
            : (
                $netProfit < 0
                    ? 'loss'
                    : 'break_even'
            );

    return [

        'summary' => [

            'total_crops' =>
                $crops->count(),

            'total_revenue' =>
                round($totalRevenue, 2),

            'total_expenses' =>
                round($totalExpenses, 2),

            'net_profit_loss' =>
                round($netProfit, 2),

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