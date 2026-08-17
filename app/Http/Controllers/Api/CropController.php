<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCropRequest;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\Plot;
use Illuminate\Http\Request;

class CropController extends Controller
{
    /**
     * Display all crops belonging to a plot.
     */
    public function index(Request $request, Farm $farm, Plot $plot)
    {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        $crops = $plot->crops()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Crops retrieved successfully.',
            'data' => $crops,
        ]);
    }

    /**
     * Store a new crop inside a plot.
     */
    public function store(
        StoreCropRequest $request,
        Farm $farm,
        Plot $plot
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        $crop = $plot->crops()->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Crop created successfully.',
            'data' => $crop,
        ], 201);
    }

    /**
     * Display a specific crop.
     */
    public function show(
        Request $request,
        Farm $farm,
        Plot $plot,
        Crop $crop
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        if ($crop->plot_id !== $plot->id) {
            return response()->json([
                'success' => false,
                'message' => 'This crop does not belong to this plot.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Crop retrieved successfully.',
            'data' => $crop,
        ]);
    }

    /**
     * Update a crop.
     */
    public function update(
        StoreCropRequest $request,
        Farm $farm,
        Plot $plot,
        Crop $crop
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        if ($crop->plot_id !== $plot->id) {
            return response()->json([
                'success' => false,
                'message' => 'This crop does not belong to this plot.',
            ], 404);
        }

        $crop->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Crop updated successfully.',
            'data' => $crop->fresh(),
        ]);
    }

    /**
     * Delete a crop.
     */
    public function destroy(
        Request $request,
        Farm $farm,
        Plot $plot,
        Crop $crop
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        if ($crop->plot_id !== $plot->id) {
            return response()->json([
                'success' => false,
                'message' => 'This crop does not belong to this plot.',
            ], 404);
        }

        $crop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Crop deleted successfully.',
        ]);
    }
}