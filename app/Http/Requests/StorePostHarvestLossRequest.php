<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostHarvestLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loss_date' => [
                'required',
                'date',
            ],

            'quantity_lost' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],

            'loss_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}