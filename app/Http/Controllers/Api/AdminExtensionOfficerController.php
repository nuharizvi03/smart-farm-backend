<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminExtensionOfficerController extends Controller
{
    private function authorizeAdmin(Request $request): ?JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
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
            'message' => 'Extension officers retrieved successfully.',
            'data' => $officers,
        ]);
    }

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
            'message' => 'Extension officer created successfully.',
            'data' => $officer,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an extension officer.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Extension officer retrieved successfully.',
            'data' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an extension officer.',
            ], 422);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Extension officer updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an extension officer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Deleted users cannot be activated. Restore the account first.',
            ], 422);
        }

        $user->update([
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension officer activated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an extension officer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This extension officer has already been deleted.',
            ], 422);
        }

        $user->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension officer deactivated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'extension_officer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an extension officer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This extension officer has already been deleted.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Extension officer deleted successfully.',
        ]);
    }
}