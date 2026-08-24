<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;

class InputUsageReportController extends Controller
{
    /**
     * FR-10.6
     * Fertilizer & Pesticide Usage Report per Crop.
     */
    public function cropUsage(Crop $crop)
    {
        $applications = $crop->inputApplications()
            ->with('agrochemicalProduct')
            ->orderByDesc('application_date')
            ->get();

        $totalCost = $applications->sum(function ($application) {
            return (float) $application->total_cost;
        });

        $fertilizerApplications = $applications
            ->where('input_type', 'fertilizer')
            ->values();

        $pesticideApplications = $applications
            ->where('input_type', 'pesticide')
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Fertilizer and pesticide usage report generated successfully.',
            'data' => [
                'crop' => [
                    'id' => $crop->id,
                    'crop_name' => $crop->crop_name,
                    'variety' => $crop->variety,
                    'season' => $crop->season,
                ],

                'summary' => [
                    'total_applications' => $applications->count(),
                    'fertilizer_applications' => $fertilizerApplications->count(),
                    'pesticide_applications' => $pesticideApplications->count(),
                    'total_cost' => round($totalCost, 2),
                ],

                'applications' => $applications->map(function ($application) {
                    return [
                        'id' => $application->id,
                        'input_type' => $application->input_type,
                        'product' => $application->product_name,
                        'product_id' => $application->agrochemical_product_id,
                        'date' => $application->application_date,
                        'quantity' => (float) $application->quantity_applied,
                        'unit' => $application->unit,
                        'unit_cost' => (float) $application->unit_cost,
                        'total_cost' => (float) $application->total_cost,
                        'notes' => $application->notes,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * FR-10.6 CSV Export
     */
    public function cropUsageCsv(Crop $crop)
    {
        $applications = $crop->inputApplications()
            ->orderByDesc('application_date')
            ->get();

        $filename = 'input-usage-report-' . $crop->id . '.csv';

        return response()->streamDownload(function () use ($crop, $applications) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Fertilizer & Pesticide Usage Report']);
            fputcsv($handle, []);

            fputcsv($handle, ['Crop Information']);
            fputcsv($handle, ['Crop ID', $crop->id]);
            fputcsv($handle, ['Crop Name', $crop->crop_name]);
            fputcsv($handle, ['Variety', $crop->variety]);
            fputcsv($handle, ['Season', $crop->season]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Product',
                'Input Type',
                'Application Date',
                'Quantity',
                'Unit',
                'Unit Cost',
                'Total Cost',
                'Notes'
            ]);

            foreach ($applications as $application) {
                fputcsv($handle, [
                    $application->product_name,
                    $application->input_type,
                    $application->application_date,
                    $application->quantity_applied,
                    $application->unit,
                    $application->unit_cost,
                    $application->total_cost,
                    $application->notes,
                ]);
            }

            fputcsv($handle, []);

            fputcsv($handle, [
                'Total Applications',
                $applications->count()
            ]);

            fputcsv($handle, [
                'Total Cost',
                $applications->sum('total_cost')
            ]);

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}