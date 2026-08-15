<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_name' => 'required|string|max:255',

            'location' => 'required|string|max:255',

            'district' => 'required|string|max:100',

            'province' => 'nullable|string|max:100',

            'total_area' => 'required|numeric|min:0.01',

            'area_unit' => 'required|string|in:acres,hectares',

            'description' => 'nullable|string|max:1000',
        ];
    }
}