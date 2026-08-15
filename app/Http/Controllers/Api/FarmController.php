<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmRequest;
use App\Models\Farm;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Display all farms belonging to the authenticated user.
     */
    public function index(Request $request)
    {
        $farms = $request->user()
            ->farms()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Farms retrieved successfully.',
            'data' => $farms,
        ]);
    }

    /**
     * Store a new farm.
     */
    public function store(StoreFarmRequest $request)
    {
        $farm = $request->user()->farms()->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Farm created successfully.',
            'data' => $farm,
        ], 201);
    }

    /**
     * Display a specific farm.
     */
    public function show(Request $request, Farm $farm)
    {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this farm.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Farm retrieved successfully.',
            'data' => $farm,
        ]);
    }

    /**
     * Update a farm.
     */
    public function update(StoreFarmRequest $request, Farm $farm)
    {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this farm.',
            ], 403);
        }

        $farm->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Farm updated successfully.',
            'data' => $farm->fresh(),
        ]);
    }

    /**
     * Delete a farm.
     */
    public function destroy(Request $request, Farm $farm)
    {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this farm.',
            ], 403);
        }

        $farm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Farm deleted successfully.',
        ]);
    }
}