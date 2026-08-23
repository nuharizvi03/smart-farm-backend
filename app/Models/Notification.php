<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'crop_id',
        'type',
        'title',
        'message',
        'scheduled_at',
        'read_at',
        'dismissed_at',
        'snoozed_until',
        'related_type',
        'related_id',
        'data',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Notification belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Notification may belong to a crop.
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}