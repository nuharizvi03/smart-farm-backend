<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class FarmerController extends Controller
{
    public function index()
    {
        $farms = Farm::where('user_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'data' => $farms
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_name' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        $farm = Farm::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Farm created successfully.',
            'data' => $farm
        ], 201);
    }

    public function show(Farm $farm)
    {
        return response()->json([
            'success' => true,
            'data' => $farm
        ]);
    }

    public function update(Request $request, Farm $farm)
    {
        $validated = $request->validate([
            'farm_name' => 'sometimes|string|max:255',
            'district' => 'sometimes|string|max:255',
            'province' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $farm->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Farm updated successfully.',
            'data' => $farm->fresh()
        ]);
    }

    public function destroy(Farm $farm)
    {
        $farm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Farm deleted successfully.'
        ]);
    }
}