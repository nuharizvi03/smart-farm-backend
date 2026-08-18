<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    protected $fillable = [
        'plot_id',
        'crop_name',
        'variety',
        'planting_date',
        'expected_harvest_date',
        'season',
        'status',
        'notes',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
    ];

    /**
     * A crop belongs to a plot.
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}