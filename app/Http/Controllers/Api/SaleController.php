<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Harvest;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display all sales for a harvest.
     */
    public function index(Harvest $harvest)
    {
        $sales = $harvest->sales()
            ->orderByDesc('sale_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Sales retrieved successfully.',
            'data' => $sales,
        ]);
    }

    /**
     * Create a new sale.
     */
    public function store(
        StoreSaleRequest $request,
        Harvest $harvest
    ) {
        $validated = $request->validated();

        $totalSold = $harvest->sales()
            ->sum('quantity_sold');

        $availableQuantity =
            $harvest->quantity_harvested - $totalSold;

        if ($validated['quantity_sold'] > $availableQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Sale quantity exceeds available harvested quantity.',
                'available_quantity' => $availableQuantity,
            ], 422);
        }

        if (
            $validated['payment_status'] !== 'unpaid' &&
            empty($validated['payment_date'])
        ) {
            $validated['payment_date'] = now()->toDateString();
        }

        if ($validated['payment_status'] === 'unpaid') {
            $validated['payment_date'] = null;
        }

        $sale = $harvest->sales()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data' => $sale,
        ], 201);
    }

    /**
     * Display one sale.
     */
    public function show(
        Harvest $harvest,
        Sale $sale
    ) {
        if ($sale->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Sale does not belong to this harvest.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    /**
     * Update a sale.
     */
    public function update(
        StoreSaleRequest $request,
        Harvest $harvest,
        Sale $sale
    ) {
        if ($sale->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Sale does not belong to this harvest.',
            ], 404);
        }

        $validated = $request->validated();

        $otherSalesQuantity = $harvest->sales()
            ->where('id', '!=', $sale->id)
            ->sum('quantity_sold');

        $availableQuantity =
            $harvest->quantity_harvested - $otherSalesQuantity;

        if ($validated['quantity_sold'] > $availableQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Sale quantity exceeds available harvested quantity.',
                'available_quantity' => $availableQuantity,
            ], 422);
        }

        if (
            $validated['payment_status'] !== 'unpaid' &&
            empty($validated['payment_date'])
        ) {
            $validated['payment_date'] = now()->toDateString();
        }

        if ($validated['payment_status'] === 'unpaid') {
            $validated['payment_date'] = null;
        }

        $sale->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
            'data' => $sale->fresh(),
        ]);
    }

    /**
     * Delete a sale.
     */
    public function destroy(
        Harvest $harvest,
        Sale $sale
    ) {
        if ($sale->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Sale does not belong to this harvest.',
            ], 404);
        }

        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully.',
        ]);
    }
}