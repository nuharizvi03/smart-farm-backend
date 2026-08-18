<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'farm_id',
        'crop_id',
        'category',
        'amount',
        'expense_date',
        'description',
        'receipt_path',
    ];

    /**
     * The farm this expense belongs to.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * The crop this expense belongs to.
     * Null for farm-wide expenses.
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}