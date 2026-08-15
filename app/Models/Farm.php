<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Plot;

class Farm extends Model
{
    protected $fillable = [
        'user_id',
        'farm_name',
        'location',
        'district',
        'province',
        'total_area',
        'area_unit',
        'description',
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
    ];

    /**
     * Farm belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plots(): HasMany
{
    return $this->hasMany(Plot::class);
}

    /**
     * Farm will have many plots.
     *
     * We will create the Plot model later.
     */
    // public function plots(): HasMany
    // {
    //     return $this->hasMany(Plot::class);
    // }
}