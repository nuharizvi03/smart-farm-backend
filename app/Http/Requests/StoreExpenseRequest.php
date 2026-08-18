<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'crop_id' => [
                'nullable',
                'integer',
                'exists:crops,id',
            ],

            'category' => [
                'required',
                'string',
                'max:100',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'expense_date' => [
                'required',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'receipt_path' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}