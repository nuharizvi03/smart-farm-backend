<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateHarvestReminders extends Command
{
    protected $signature = 'notifications:harvest-reminders';

    protected $description =
        'Generate harvest reminder notifications 3 days before expected harvest date';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(3);

        $this->info(
            'Checking harvests for ' .
            $targetDate->toDateString()
        );

        $crops = Crop::whereNotNull('expected_harvest_date')
            ->whereDate(
                'expected_harvest_date',
                $targetDate
            )
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereIn('status', [
                        'active',
                        'growing',
                        'planted',
                    ]);
            })
            ->get();

        $created = 0;

        foreach ($crops as $crop) {

            /*
            |--------------------------------------------------------------------------
            | Get user through Plot -> Farm -> User
            |--------------------------------------------------------------------------
            */

            $crop->loadMissing(
                'plot.farm'
            );

            $farm = $crop->plot?->farm;

            if (!$farm) {
                $this->warn(
                    "Crop {$crop->id} has no farm."
                );

                continue;
            }

            $userId = $farm->user_id;

            /*
            |--------------------------------------------------------------------------
            | Check notification preference
            |--------------------------------------------------------------------------
            */

            $preference =
                NotificationPreference::firstOrCreate(
                    [
                        'user_id' => $userId,
                    ],
                    [
                        'manual_reminders' => true,
                        'harvest_reminders' => true,
                        'input_application_reminders' => true,
                        'weather_alerts' => true,
                    ]
                );

            if (!$preference->harvest_reminders) {
                $this->info(
                    "Harvest reminders disabled for user {$userId}."
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate notification
            |--------------------------------------------------------------------------
            */

            $alreadyExists = Notification::where(
                'user_id',
                $userId
            )
            ->where(
                'crop_id',
                $crop->id
            )
            ->where(
                'type',
                'harvest_reminder'
            )
            ->whereDate(
                'scheduled_at',
                $targetDate
            )
            ->exists();

            if ($alreadyExists) {
                $this->info(
                    "Reminder already exists for crop {$crop->id}."
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create notification
            |--------------------------------------------------------------------------
            */

            Notification::create([
                'user_id' => $userId,

                'crop_id' => $crop->id,

                'type' => 'harvest_reminder',

                'title' =>
                    'Harvest Reminder',

                'message' =>
                    "The expected harvest date for {$crop->crop_name} is " .
                    Carbon::parse(
                        $crop->expected_harvest_date
                    )->format('Y-m-d') .
                    ".",

                'scheduled_at' =>
                    Carbon::parse(
                        $crop->expected_harvest_date
                    )->subDays(3),

                'related_type' =>
                    'crop',

                'related_id' =>
                    $crop->id,

                'data' => [
                    'crop_id' =>
                        $crop->id,

                    'expected_harvest_date' =>
                        Carbon::parse(
                            $crop->expected_harvest_date
                        )->toDateString(),

                    'farm_id' =>
                        $farm->id,
                ],
            ]);

            $created++;

            $this->info(
                "Harvest reminder created for crop {$crop->id}."
            );
        }

        $this->info(
            "{$created} harvest reminder(s) created."
        );

        return Command::SUCCESS;
    }
}