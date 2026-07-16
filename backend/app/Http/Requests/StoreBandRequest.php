<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:15', 'unique:bands,name'],
            'genre' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
        ];
    }
}
