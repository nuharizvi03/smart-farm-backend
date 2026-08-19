<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHarvestRequest;
use App\Models\Crop;
use App\Models\Harvest;

class HarvestController extends Controller
{
    /**
     * Display all harvests for a crop.
     */
    public function index(Crop $crop)
    {
        $harvests = $crop->harvests()
            ->with('sales')
            ->orderByDesc('harvest_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Harvests retrieved successfully.',
            'data' => $harvests,
        ]);
    }

    /**
     * Create a new harvest for a crop.
     */
    public function store(
        StoreHarvestRequest $request,
        Crop $crop
    ) {
        $harvest = $crop->harvests()->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Harvest created successfully.',
            'data' => $harvest,
        ], 201);
    }

    /**
     * Display a single harvest.
     */
    public function show(
        Crop $crop,
        Harvest $harvest
    ) {
        if ($harvest->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest does not belong to this crop.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $harvest->load('sales'),
        ]);
    }

    /**
     * Update a harvest.
     */
    public function update(
        StoreHarvestRequest $request,
        Crop $crop,
        Harvest $harvest
    ) {
        if ($harvest->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest does not belong to this crop.',
            ], 404);
        }

        $harvest->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Harvest updated successfully.',
            'data' => $harvest->fresh(),
        ]);
    }

    /**
     * Delete a harvest.
     */
    public function destroy(
        Crop $crop,
        Harvest $harvest
    ) {
        if ($harvest->crop_id !== $crop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest does not belong to this crop.',
            ], 404);
        }

        $harvest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Harvest deleted successfully.',
        ]);
    }
}