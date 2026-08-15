<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlotRequest;
use App\Models\Farm;
use App\Models\Plot;
use Illuminate\Http\Request;

class PlotController extends Controller
{
    /**
     * Display all plots belonging to a farm.
     */
    public function index(Request $request, Farm $farm)
    {
        // Make sure the farm belongs to the authenticated user.
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        $plots = $farm->plots()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Plots retrieved successfully.',
            'data' => $plots,
        ]);
    }

    /**
     * Store a new plot inside a farm.
     */
    public function store(
        StorePlotRequest $request,
        Farm $farm
    ) {
        // Make sure the farm belongs to the authenticated user.
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to add plots to this farm.',
            ], 403);
        }

        $plot = $farm->plots()->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Plot created successfully.',
            'data' => $plot,
        ], 201);
    }

    /**
     * Display a specific plot.
     */
    public function show(
        Request $request,
        Farm $farm,
        Plot $plot
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        // Make sure the plot actually belongs to this farm.
        if ($plot->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'This plot does not belong to this farm.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plot retrieved successfully.',
            'data' => $plot,
        ]);
    }

    /**
     * Update a plot.
     */
    public function update(
        StorePlotRequest $request,
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

        $plot->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Plot updated successfully.',
            'data' => $plot->fresh(),
        ]);
    }

    /**
     * Delete a plot.
     */
    public function destroy(
        Request $request,
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

        $plot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plot deleted successfully.',
        ]);
    }
}