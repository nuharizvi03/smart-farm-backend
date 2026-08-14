<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',

            'district' => 'required|string|max:100',
            'province' => 'nullable|string|max:100',

            'farm_name' => 'nullable|string|max:255',
        ];
    }
}