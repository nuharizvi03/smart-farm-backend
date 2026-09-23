<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * Display audit logs.
     *
     * Audit logs are accessible only to administrators.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $auditLogs = AuditLog::with([
            'user:id,full_name,email,role',
        ])
            ->latest('created_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully.',
            'data' => $auditLogs,
        ]);
    }
}