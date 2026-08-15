<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plot_name' => 'required|string|max:255',

            'area' => 'required|numeric|min:0.01',

            'area_unit' => 'required|string|in:acres,hectares',

            'soil_type' => 'nullable|string|max:100',

            'description' => 'nullable|string|max:1000',
        ];
    }
}