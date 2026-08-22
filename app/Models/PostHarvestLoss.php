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
        'loss_date',
        'quantity_lost',
        'unit',
        'reason',
        'loss_amount',
        'notes',
    ];

    protected $casts = [
        'loss_date' => 'date:Y-m-d',
        'quantity_lost' => 'decimal:2',
        'loss_amount' => 'decimal:2',
    ];

    /**
     * The harvest this loss belongs to.
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}