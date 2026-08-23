<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'manual_reminders',
        'harvest_reminders',
        'input_application_reminders',
        'weather_alerts',
    ];

    protected $casts = [
        'manual_reminders' => 'boolean',
        'harvest_reminders' => 'boolean',
        'input_application_reminders' => 'boolean',
        'weather_alerts' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}