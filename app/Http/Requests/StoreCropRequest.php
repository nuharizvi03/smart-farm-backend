<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCropRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crop_name' => 'required|string|max:255',

            'variety' => 'nullable|string|max:255',

            'planting_date' => 'required|date',

            'expected_harvest_date' => [
                'nullable',
                'date',
                'after_or_equal:planting_date',
            ],

            'season' => 'nullable|string|max:100',

            'status' => [
                'required',
                'string',
                'in:planned,planted,growing,harvested,cancelled',
            ],

            'notes' => 'nullable|string|max:1000',
        ];
    }
}