<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        $bandId = $this->route('band')?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:15', Rule::unique('bands', 'name')->ignore($bandId)],
            'genre' => ['sometimes', 'nullable', 'string', 'max:30'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
        ];
    }
}
