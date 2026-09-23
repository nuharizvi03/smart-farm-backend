<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Ensure the authenticated user is an administrator.
     */
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

    /**
     * Display all Farmer user accounts.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $farmers = User::withTrashed()
            ->where('role', 'farmer')
            ->select([
                'id',
                'full_name',
                'mobile',
                'email',
                'role',
                'district',
                'province',
                'farm_name',
                'profile_photo',
                'is_active',
                'deleted_at',
                'created_at',
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Farmers retrieved successfully.',
            'data' => $farmers,
        ]);
    }

    /**
     * Activate a Farmer user account.
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'farmer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a farmer.',
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

        $this->auditLogService->log(
            $request->user(),
            'ACTIVATE_USER',
            'Administrator activated a user account.',
            [
                'target_user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Deactivate a Farmer user account.
     */
    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'farmer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a farmer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This user has already been deleted.',
            ], 422);
        }

        $user->update([
            'is_active' => false,
        ]);

        $this->auditLogService->log(
            $request->user(),
            'DEACTIVATE_USER',
            'Administrator deactivated a user account.',
            [
                'target_user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Reset password for a Farmer user account.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'farmer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a farmer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reset password for a deleted user.',
            ], 422);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->auditLogService->log(
            $request->user(),
            'RESET_USER_PASSWORD',
            'Administrator reset a user password.',
            [
                'target_user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User password reset successfully.',
        ]);
    }

    /**
     * Soft delete a Farmer user account.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        if ($user->role !== 'farmer') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a farmer.',
            ], 422);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This user has already been deleted.',
            ], 422);
        }

        $this->auditLogService->log(
            $request->user(),
            'DELETE_USER',
            'Administrator deleted a user account.',
            [
                'target_user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}