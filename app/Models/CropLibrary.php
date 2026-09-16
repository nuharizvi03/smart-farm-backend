<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CropLibrary extends Model
{
    protected $fillable = [
        'crop_name',
        'variety',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}