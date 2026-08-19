<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'harvest_date' => [
                'required',
                'date',
            ],

            'quantity_harvested' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'unit' => [
                'required',
                'in:kg,units',
            ],

            'quality_grade' => [
                'nullable',
                'string',
                'max:100',
            ],

            'storage_location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}