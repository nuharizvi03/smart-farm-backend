<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostHarvestLoss extends Model
{
    use HasFactory;

    protected $fillable = [
        'harvest_id',
        'quantity_lost',
        'unit',
        'reason',
        'loss_amount',
        'notes',
    ];

    /**
     * The harvest this loss belongs to.
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}