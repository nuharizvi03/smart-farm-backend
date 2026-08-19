<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInputApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agrochemical_product_id' => [
                'nullable',
                'exists:agrochemical_products,id',
            ],

            'input_type' => [
                'required',
                'in:fertilizer,pesticide,herbicide',
            ],

            'product_name' => [
                'required',
                'string',
                'max:255',
            ],

            'application_date' => [
                'required',
                'date',
            ],

            'quantity_applied' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'unit' => [
                'required',
                'in:kg,L,g',
            ],

            'unit_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'total_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'next_application_date' => [
                'nullable',
                'date',
                'after:application_date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'recommended_dosage' => [
            'nullable',
            'numeric',
            'min:0.01',
            ],

            'dosage_unit' => [
            'nullable',
            'string',
            'max:50',
            ],
        ];
    }
}