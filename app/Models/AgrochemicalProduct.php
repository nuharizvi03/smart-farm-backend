<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgrochemicalProduct extends Model
{
    use HasFactory;

    protected $fillable = [
    'product_name',
    'input_type',
    'brand_name',
    'active_ingredient',
    'unit',
    'default_unit_cost',
    'is_preloaded',
];

    /**
     * Input applications using this product.
     */
    public function inputApplications(): HasMany
    {
        return $this->hasMany(InputApplication::class);
    }
}