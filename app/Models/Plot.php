<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plot extends Model
{
    protected $fillable = [
        'farm_id',
        'plot_name',
        'area',
        'area_unit',
        'soil_type',
        'description',
    ];

    protected $casts = [
        'area' => 'decimal:2',
    ];

    /**
     * A plot belongs to a farm.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
 * Get all crops belonging to this plot.
 */
public function crops(): HasMany
{
    return $this->hasMany(Crop::class);
}
}