<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class BuyerSummaryReportController extends Controller
{
    /**
     * FR-10.7
     * Generate Buyer Summary Report.
     *
     * Optional:
     * ?farm_id=30
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => [
                'nullable',
                'integer',
                'exists:farms,id',
            ],
        ]);

        $query = Sale::query()
            ->with([
                'harvest.crop.plot.farm'
            ]);

        /*
        |--------------------------------------------------------------------------
        | Filter by Farm
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['farm_id'])) {
            $query->whereHas(
                'harvest.crop.plot',
                function ($query) use ($validated) {
                    $query->where(
                        'farm_id',
                        $validated['farm_id']
                    );
                }
            );
        }

        $sales = $query
            ->orderByDesc('sale_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Buyer Summary
        |--------------------------------------------------------------------------
        */

        $buyers = $sales
            ->groupBy('buyer_name')
            ->map(function ($buyerSales, $buyerName) {

                $totalQuantity = $buyerSales->sum(
                    'quantity_sold'
                );

                $totalAmount = $buyerSales->sum(
                    function ($sale) {
                        return (float) $sale->quantity_sold
                            * (float) $sale->price_per_unit;
                    }
                );

                $paidAmount = 0;

                $outstandingAmount = 0;

                foreach ($buyerSales as $sale) {

                    $amount =
                        (float) $sale->quantity_sold
                        * (float) $sale->price_per_unit;

                    if ($sale->payment_status === 'fully_paid') {

                        $paidAmount += $amount;

                    } elseif (
                        $sale->payment_status === 'partially_paid'
                    ) {

                        /*
                        |--------------------------------------------------------------
                        | No paid_amount field exists in the current sales schema,
                        | so partially paid sales are treated as outstanding.
                        |--------------------------------------------------------------
                        */

                        $outstandingAmount += $amount;

                    } else {

                        $outstandingAmount += $amount;
                    }
                }

                $paymentStatus =
                    $outstandingAmount <= 0
                        ? 'fully_paid'
                        : (
                            $paidAmount > 0
                                ? 'partially_paid'
                                : 'unpaid'
                        );

                return [
                    'buyer_name' =>
                        $buyerName,

                    'total_transactions' =>
                        $buyerSales->count(),

                    'total_quantity' =>
                        round(
                            (float) $totalQuantity,
                            2
                        ),

                    'total_amount' =>
                        round(
                            (float) $totalAmount,
                            2
                        ),

                    'amount_paid' =>
                        round(
                            (float) $paidAmount,
                            2
                        ),

                    'outstanding_amount' =>
                        round(
                            (float) $outstandingAmount,
                            2
                        ),

                    'payment_status' =>
                        $paymentStatus,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Overall Summary
        |--------------------------------------------------------------------------
        */

        $totalAmount = $buyers->sum(
            'total_amount'
        );

        $amountPaid = $buyers->sum(
            'amount_paid'
        );

        $outstandingAmount = $buyers->sum(
            'outstanding_amount'
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Buyer summary report generated successfully.',

            'data' => [

                'report' => [
                    'type' => 'buyer_summary',
                    'farm_id' =>
                        $validated['farm_id'] ?? null,
                ],

                'summary' => [

                    'total_buyers' =>
                        $buyers->count(),

                    'total_transactions' =>
                        $sales->count(),

                    'total_amount' =>
                        round(
                            (float) $totalAmount,
                            2
                        ),

                    'amount_paid' =>
                        round(
                            (float) $amountPaid,
                            2
                        ),

                    'outstanding_amount' =>
                        round(
                            (float) $outstandingAmount,
                            2
                        ),
                ],

                'buyers' =>
                    $buyers,
            ],
        ]);
    }
}