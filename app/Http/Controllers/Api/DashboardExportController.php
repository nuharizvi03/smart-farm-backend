<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardFilterService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardExportController extends Controller
{
    protected DashboardFilterService $filterService;

    public function __construct(
        DashboardFilterService $filterService
    ) {
        $this->filterService = $filterService;
    }

    /**
     * Export profit trend data as CSV.
     */
    public function profitTrend(Request $request): StreamedResponse
    {
        $controller = app(ProfitTrendController::class);

        $response = $controller->index($request);

        $responseData = $response->getData(true);

        $chartData = $responseData['data']['chart_data'] ?? [];

        return $this->downloadCsv(
            'profit-trend.csv',
            [
                'Period',
                'Revenue',
                'Expenses',
                'Post Harvest Loss',
                'Profit',
            ],
            collect($chartData)->map(function ($item) {
                return [
                    $item['period'] ?? '',
                    $item['revenue'] ?? 0,
                    $item['expenses'] ?? 0,
                    $item['post_harvest_loss'] ?? 0,
                    $item['profit'] ?? 0,
                ];
            })->toArray()
        );
    }

    /**
     * Export expense distribution data as CSV.
     */
    public function expenseDistribution(
        Request $request
    ): StreamedResponse {
        $controller = app(
            ExpenseDistributionController::class
        );

        $response = $controller->index($request);

        $responseData = $response->getData(true);

        $chartData = $responseData['data']['chart_data'] ?? [];

        return $this->downloadCsv(
            'expense-distribution.csv',
            [
                'Category',
                'Amount',
            ],
            collect($chartData)->map(function ($item) {
                return [
                    $item['category'] ?? '',
                    $item['amount'] ?? 0,
                ];
            })->toArray()
        );
    }

    /**
     * Export revenue vs expenses chart as CSV.
     */
    public function revenueExpenses(
        Request $request
    ): StreamedResponse {
        $controller = app(
            RevenueExpenseChartController::class
        );

        $response = $controller->index($request);

        $responseData = $response->getData(true);

        $chartData = $responseData['data']['chart_data'] ?? [];

        return $this->downloadCsv(
            'revenue-vs-expenses.csv',
            [
                'Crop ID',
                'Crop Name',
                'Season',
                'Total Revenue',
                'Total Expenses',
            ],
            collect($chartData)->map(function ($item) {
                return [
                    $item['crop_id'] ?? '',
                    $item['crop_name'] ?? '',
                    $item['season'] ?? '',
                    $item['total_revenue'] ?? 0,
                    $item['total_expenses'] ?? 0,
                ];
            })->toArray()
        );
    }

    /**
     * Export crop performance chart as CSV.
     */
    public function cropPerformance(
        Request $request
    ): StreamedResponse {
        $controller = app(
            CropPerformanceController::class
        );

        $response = $controller->index($request);

        $responseData = $response->getData(true);

        $chartData = $responseData['data']['chart_data'] ?? [];

        return $this->downloadCsv(
            'crop-performance.csv',
            [
                'Crop Name',
                'Total Harvested',
                'Total Revenue',
                'Total Expenses',
                'Profit',
                'Profit Margin Percentage',
            ],
            collect($chartData)->map(function ($item) {
                return [
                    $item['crop_name'] ?? '',
                    $item['total_harvested'] ?? 0,
                    $item['total_revenue'] ?? 0,
                    $item['total_expenses'] ?? 0,
                    $item['profit'] ?? 0,
                    $item['profit_margin_percentage'] ?? 0,
                ];
            })->toArray()
        );
    }

    /**
     * Generate CSV download response.
     */
    private function downloadCsv(
        string $filename,
        array $headers,
        array $rows
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($headers, $rows) {

                $handle = fopen('php://output', 'w');

                fputcsv($handle, $headers);

                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
            ]
        );
    }
}