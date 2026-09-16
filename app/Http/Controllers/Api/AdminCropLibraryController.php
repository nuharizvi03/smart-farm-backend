<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CropLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCropLibraryController extends Controller
{
    private function authorizeAdmin(Request $request): ?JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $crops = CropLibrary::orderBy('crop_name')
            ->orderBy('variety')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Global crop library retrieved successfully.',
            'data' => $crops,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'crop_name' => ['required', 'string', 'max:255'],
            'variety' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $exists = CropLibrary::where('crop_name', $validated['crop_name'])
            ->where('variety', $validated['variety'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This crop and variety already exist in the global crop library.',
            ], 422);
        }

        $crop = CropLibrary::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Crop added to global crop library successfully.',
            'data' => $crop,
        ], 201);
    }

    public function update(
        Request $request,
        CropLibrary $cropLibrary
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'crop_name' => ['required', 'string', 'max:255'],
            'variety' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $duplicate = CropLibrary::where('crop_name', $validated['crop_name'])
            ->where('variety', $validated['variety'] ?? null)
            ->where('id', '!=', $cropLibrary->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This crop and variety already exist in the global crop library.',
            ], 422);
        }

        $cropLibrary->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Crop library item updated successfully.',
            'data' => $cropLibrary->fresh(),
        ]);
    }

    public function activate(
        Request $request,
        CropLibrary $cropLibrary
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $cropLibrary->update([
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Crop library item activated successfully.',
            'data' => $cropLibrary->fresh(),
        ]);
    }

    public function deactivate(
        Request $request,
        CropLibrary $cropLibrary
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $cropLibrary->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Crop library item deactivated successfully.',
            'data' => $cropLibrary->fresh(),
        ]);
    }
}