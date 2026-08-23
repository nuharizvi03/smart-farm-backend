<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    /**
     * Get the authenticated user's notification preferences.
     *
     * FR-09.7
     */
    public function show(Request $request)
    {
        $preference = NotificationPreference::firstOrCreate(
            [
                'user_id' => $request->user()->id,
            ],
            [
                'manual_reminders' => true,
                'harvest_reminders' => true,
                'input_application_reminders' => true,
                'weather_alerts' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences retrieved successfully.',
            'data' => $preference,
        ]);
    }

    /**
     * Update notification preferences.
     *
     * FR-09.7
     */
    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'manual_reminders' =>
                    'sometimes|boolean',

                'harvest_reminders' =>
                    'sometimes|boolean',

                'input_application_reminders' =>
                    'sometimes|boolean',

                'weather_alerts' =>
                    'sometimes|boolean',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $preference = NotificationPreference::firstOrCreate(
            [
                'user_id' => $request->user()->id,
            ],
            [
                'manual_reminders' => true,
                'harvest_reminders' => true,
                'input_application_reminders' => true,
                'weather_alerts' => true,
            ]
        );

        $preference->update(
            $request->only([
                'manual_reminders',
                'harvest_reminders',
                'input_application_reminders',
                'weather_alerts',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
            'data' => $preference->fresh(),
        ]);
    }
}