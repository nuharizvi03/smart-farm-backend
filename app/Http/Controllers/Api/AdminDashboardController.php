<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crop;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Display system administration dashboard statistics.
     */
    public function index(Request $request): JsonResponse
    {
        // Admin-only protection
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Total registered farmers
        $totalFarmers = User::where('role', 'farmer')->count();

        // Total crop plans
        $totalCropPlans = Crop::count();

        // Active Sanctum sessions/tokens
        $activeSessions = DB::table('personal_access_tokens')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        // System error/failure summary
        $totalSystemErrors = DB::table('failed_jobs')->count();

        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard statistics retrieved successfully.',

            'data' => [
                'total_farmers' => $totalFarmers,
                'total_crop_plans' => $totalCropPlans,
                'active_sessions' => $activeSessions,

                'system_errors' => [
                    'total' => $totalSystemErrors,
                ],
            ],
        ]);
    }
}