<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'harvest_id',
        'buyer_name',
        'buyer_contact',
        'sale_date',
        'quantity_sold',
        'price_per_unit',
        'payment_status',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date:Y-m-d',
            'payment_date' => 'date:Y-m-d',
            'quantity_sold' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
        ];
    }

    /**
     * Harvest this sale belongs to.
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}