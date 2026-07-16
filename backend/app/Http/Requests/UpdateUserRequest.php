<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:20', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }
}
