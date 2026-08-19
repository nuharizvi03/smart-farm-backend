<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InputApplication extends Model
{
    use HasFactory;

    protected $fillable = [
    'crop_id',
    'agrochemical_product_id',
    'input_type',
    'product_name',
    'application_date',
    'quantity_applied',
    'unit',
    'unit_cost',
    'total_cost',
    'recommended_dosage',
    'dosage_unit',
    'next_application_date',
    'notes',
    ];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function agrochemicalProduct()
    {
        return $this->belongsTo(AgrochemicalProduct::class);
    }
}