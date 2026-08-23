<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\InputApplication;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * FR-09.2
     *
     * Create harvest reminders 3 days before
     * the expected harvest date of active crops.
     */
    public function generateHarvestReminders(): int
    {
        $created = 0;

        $targetDate = Carbon::today()->addDays(3);

        $crops = Crop::whereDate(
            'expected_harvest_date',
            $targetDate
        )
        ->whereIn('status', [
            'active',
            'Active',
            'ACTIVE',
        ])
        ->with('plot.farm')
        ->get();

        foreach ($crops as $crop) {

            $farm = $crop->plot?->farm;

            if (!$farm) {
                continue;
            }

            $userId = $farm->user_id;

            $preference = $this->getPreference($userId);

            if (!$preference->harvest_reminders) {
                continue;
            }

            /*
             * Prevent duplicate notifications.
             */
            $exists = Notification::where(
                'user_id',
                $userId
            )
            ->where('crop_id', $crop->id)
            ->where(
                'type',
                'harvest_reminder'
            )
            ->whereDate(
                'scheduled_at',
                $targetDate
            )
            ->exists();

            if ($exists) {
                continue;
            }

            Notification::create([
                'user_id' => $userId,

                'crop_id' => $crop->id,

                'type' => 'harvest_reminder',

                'title' => 'Upcoming Harvest',

                'message' =>
                    'The expected harvest date for '
                    . $crop->crop_name
                    . ' is '
                    . Carbon::parse(
                        $crop->expected_harvest_date
                    )->format('d M Y')
                    . '.',

                'scheduled_at' =>
                    $targetDate->copy()->startOfDay(),

                'data' => [
                    'source' => 'automatic',
                    'reminder_type' => 'harvest',
                    'expected_harvest_date' =>
                        $crop->expected_harvest_date,
                ],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * FR-09.3
     *
     * Create fertilizer/pesticide application
     * reminders when the next application date
     * arrives.
     */
    public function generateInputApplicationReminders(): int
    {
        $created = 0;

        $today = Carbon::today();

        $applications = InputApplication::whereDate(
            'next_application_date',
            $today
        )
        ->with('crop.plot.farm')
        ->get();

        foreach ($applications as $application) {

            $crop = $application->crop;

            $farm = $crop?->plot?->farm;

            if (!$farm) {
                continue;
            }

            $userId = $farm->user_id;

            $preference = $this->getPreference($userId);

            if (!$preference->input_application_reminders) {
                continue;
            }

            /*
             * Prevent duplicate notifications.
             */
            $exists = Notification::where(
                'user_id',
                $userId
            )
            ->where(
                'crop_id',
                $application->crop_id
            )
            ->where(
                'type',
                'input_application_reminder'
            )
            ->where(
                'related_type',
                InputApplication::class
            )
            ->where(
                'related_id',
                $application->id
            )
            ->whereDate(
                'scheduled_at',
                $today
            )
            ->exists();

            if ($exists) {
                continue;
            }

            $productName =
                $application->product_name
                ?? 'input application';

            Notification::create([
                'user_id' => $userId,

                'crop_id' =>
                    $application->crop_id,

                'type' =>
                    'input_application_reminder',

                'title' =>
                    'Input Application Reminder',

                'message' =>
                    'The next application of '
                    . $productName
                    . ' for '
                    . ($crop->crop_name ?? 'your crop')
                    . ' is scheduled for today.',

                'scheduled_at' =>
                    $today->copy()->startOfDay(),

                'related_type' =>
                    InputApplication::class,

                'related_id' =>
                    $application->id,

                'data' => [
                    'source' => 'automatic',
                    'reminder_type' =>
                        'input_application',

                    'input_application_id' =>
                        $application->id,

                    'next_application_date' =>
                        $application->next_application_date,
                ],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Generate all automatic notifications.
     *
     * FR-09.2 + FR-09.3
     */
    public function generateAutomaticNotifications(): array
    {
        $harvest =
            $this->generateHarvestReminders();

        $inputApplications =
            $this->generateInputApplicationReminders();

        return [
            'harvest_reminders' => $harvest,

            'input_application_reminders' =>
                $inputApplications,

            'total' =>
                $harvest + $inputApplications,
        ];
    }

    /**
     * Get or create notification preferences.
     */
    protected function getPreference(
        int $userId
    ): NotificationPreference {

        return NotificationPreference::firstOrCreate(
            [
                'user_id' => $userId,
            ],
            [
                'manual_reminders' => true,

                'harvest_reminders' => true,

                'input_application_reminders' =>
                    true,

                'weather_alerts' => true,
            ]
        );
    }
}