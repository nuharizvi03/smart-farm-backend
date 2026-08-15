<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Show authenticated user's profile.
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $user->update($request->only([
            'full_name',
            'mobile',
            'district',
            'province',
            'farm_name',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Change password.
     *
     * We will implement proper validation later.
     */
    public function changePassword(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Change password endpoint is available.',
        ]);
    }

    /**
     * Upload profile photo.
     *
     * We will implement file upload later.
     */
    public function uploadPhoto(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile photo endpoint is available.',
        ]);
    }
}