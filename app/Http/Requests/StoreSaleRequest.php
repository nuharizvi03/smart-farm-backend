<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buyer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'buyer_contact' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sale_date' => [
                'required',
                'date',
            ],

            'quantity_sold' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'price_per_unit' => [
                'required',
                'numeric',
                'min:0',
            ],

            'payment_status' => [
                'required',
                'in:unpaid,partially_paid,fully_paid',
            ],

            'payment_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}