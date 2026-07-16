<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint publik
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // username ATAU email, dicek di controller/service
            'password' => ['required', 'string'],
        ];
    }
}
