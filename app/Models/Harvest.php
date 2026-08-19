<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Harvest extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'harvest_date',
        'quantity_harvested',
        'unit',
        'quality_grade',
        'storage_location',
        'notes',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'quantity_harvested' => 'decimal:2',
    ];

    /**
     * Crop this harvest belongs to.
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * Sales made from this harvest.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Post-harvest losses from this harvest.
     */
    public function postHarvestLosses(): HasMany
    {
        return $this->hasMany(PostHarvestLoss::class);
    }

}