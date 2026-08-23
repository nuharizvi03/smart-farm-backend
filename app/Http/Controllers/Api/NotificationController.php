<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where(
            'user_id',
            $request->user()->id
        )
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Create manual reminder.
     *
     * FR-09.1
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:255',

                'message' => 'nullable|string',

                'scheduled_at' =>
                    'required|date',

                'crop_id' => [
    'nullable',
    'exists:crops,id',
    function ($attribute, $value, $fail) use ($request) {
        if ($value) {
            $exists = \App\Models\Crop::where('id', $value)
                ->whereHas('plot.farm', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
                ->exists();

            if (!$exists) {
                $fail('The selected crop does not belong to your farm.');
            }
        }
    },
],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------
        | Check manual reminder preference
        |--------------------------------------------------------------
        */

        $preference =
            NotificationPreference::firstOrCreate(
                [
                    'user_id' =>
                        $request->user()->id,
                ],
                [
                    'manual_reminders' => true,
                    'harvest_reminders' => true,
                    'input_application_reminders' => true,
                    'weather_alerts' => true,
                ]
            );

        if (!$preference->manual_reminders) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Manual reminders are disabled in notification preferences.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------
        | Create notification
        |--------------------------------------------------------------
        */

        $notification = Notification::create([
            'user_id' =>
                $request->user()->id,

            'crop_id' =>
                $request->crop_id,

            'type' =>
                'manual_reminder',

            'title' =>
                $request->title,

            'message' =>
                $request->message,

            'scheduled_at' =>
                $request->scheduled_at,

            'data' => [
                'source' => 'manual',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Manual reminder created successfully.',

            'data' =>
                $notification,
        ], 201);
    }

    /**
     * Mark notification as read.
     *
     * FR-09.4
     */
    public function markAsRead(
        Request $request,
        Notification $notification
    ) {
        if (
            $notification->user_id !==
            $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Notification marked as read.',
            'data' => $notification,
        ]);
    }

    /**
     * Dismiss notification.
     *
     * FR-09.5
     */
    public function dismiss(
        Request $request,
        Notification $notification
    ) {
        if (
            $notification->user_id !==
            $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $notification->update([
            'dismissed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Notification dismissed.',
            'data' => $notification,
        ]);
    }

    /**
     * Snooze notification.
     *
     * FR-09.5
     */
    public function snooze(
        Request $request,
        Notification $notification
    ) {
        if (
            $notification->user_id !==
            $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'snoozed_until' =>
                    'required|date|after:now',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification->update([
            'snoozed_until' =>
                $request->snoozed_until,
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Notification snoozed successfully.',
            'data' => $notification,
        ]);
    }
}