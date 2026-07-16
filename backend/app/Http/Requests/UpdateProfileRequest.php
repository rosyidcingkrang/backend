<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga oleh middleware auth:sanctum di route
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:15'],
        ];
    }
}
