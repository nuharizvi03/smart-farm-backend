<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Ensure the authenticated user is an administrator.
     */
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

    /**
     * Display all Extension Officer accounts.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $officers = User::withTrashed()
            ->where('role', 'extension_officer')
            ->select([
                'id',
                'full_name',
                'mobile',
                'email',
                'role',
                'district',
                'province',
                'is_active',
                'deleted_at',
                'created_at',
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Extension Officers retrieved successfully.',
            'data' => $officers,
        ]);
    }

    /**
     * Create a new Extension Officer account.
     */
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);

        $officer = User::create([
            'full_name' => $validated['full_name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'extension_officer',
            'district' => $validated['district'],
            'province' => $validated['province'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer created successfully.',
            'data' => [
                'id' => $officer->id,
                'full_name' => $officer->full_name,
                'mobile' => $officer->mobile,
                'email' => $officer->email,
                'role' => $officer->role,
                'district' => $officer->district,
                'province' => $officer->province,
                'is_active' => $officer->is_active,
            ],
        ], 201);
    }

    /**
     * Display one Extension Officer.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an Extension Officer.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'role' => $user->role,
                'district' => $user->district,
                'province' => $user->province,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update an Extension Officer.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an Extension Officer.',
            ], 404);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer updated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'role' => $user->role,
                'district' => $user->district,
                'province' => $user->province,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Activate an Extension Officer.
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an Extension Officer.',
            ], 404);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Deleted Extension Officers cannot be activated.',
            ], 422);
        }

        $user->update([
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer activated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Deactivate an Extension Officer.
     */
    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an Extension Officer.',
            ], 404);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This Extension Officer has already been deleted.',
            ], 422);
        }

        $user->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer deactivated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Soft-delete an Extension Officer.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an Extension Officer.',
            ], 404);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This Extension Officer has already been deleted.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer deleted successfully.',
        ]);
    }
}