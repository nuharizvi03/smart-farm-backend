<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostHarvestLossRequest;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\PostHarvestLoss;
use Illuminate\Support\Facades\DB;

class PostHarvestLossController extends Controller
{
    /**
     * Display all post-harvest losses for a harvest.
     */
    public function index(Harvest $harvest)
    {
        $losses = $harvest->postHarvestLosses()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Post-harvest losses retrieved successfully.',
            'data' => $losses,
        ]);
    }

    /**
     * Record a post-harvest loss and create an expense.
     */
    public function store(
        StorePostHarvestLossRequest $request,
        Harvest $harvest
    ) {
        $validated = $request->validated();

        DB::transaction(function () use (
            $validated,
            $harvest,
            &$loss
        ) {
            $loss = $harvest->postHarvestLosses()->create([
                'quantity_lost' => $validated['quantity_lost'],
                'unit' => $validated['unit'],
                'reason' => $validated['reason'],
                'loss_amount' => $validated['loss_amount'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $crop = $harvest->crop;

            Expense::create([
                'farm_id' => $crop->plot->farm_id,
                'crop_id' => $crop->id,
                'category' => 'Post-Harvest Loss',
                'amount' => $loss->loss_amount,
                'expense_date' => now()->toDateString(),
                'description' =>
                    'Post-harvest loss: ' .
                    $loss->reason .
                    ' (' .
                    $loss->quantity_lost .
                    ' ' .
                    $loss->unit .
                    ')',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Post-harvest loss and expense created successfully.',
            'data' => $loss,
        ], 201);
    }

    /**
     * Display one post-harvest loss.
     */
    public function show(
        Harvest $harvest,
        PostHarvestLoss $postHarvestLoss
    ) {
        if ($postHarvestLoss->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Post-harvest loss does not belong to this harvest.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $postHarvestLoss,
        ]);
    }

    /**
     * Update a post-harvest loss.
     */
    public function update(
        StorePostHarvestLossRequest $request,
        Harvest $harvest,
        PostHarvestLoss $postHarvestLoss
    ) {
        if ($postHarvestLoss->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Post-harvest loss does not belong to this harvest.',
            ], 404);
        }

        $postHarvestLoss->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Post-harvest loss updated successfully.',
            'data' => $postHarvestLoss->fresh(),
        ]);
    }

    /**
     * Delete a post-harvest loss.
     */
    public function destroy(
        Harvest $harvest,
        PostHarvestLoss $postHarvestLoss
    ) {
        if ($postHarvestLoss->harvest_id !== $harvest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Post-harvest loss does not belong to this harvest.',
            ], 404);
        }

        $postHarvestLoss->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post-harvest loss deleted successfully.',
        ]);
    }
}