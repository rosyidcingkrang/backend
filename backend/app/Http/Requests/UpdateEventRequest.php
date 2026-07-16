<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:50'],
            'event_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'venue_note' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
        // slots TIDAK diubah lewat endpoint ini — dikelola via endpoint slot terpisah
        // supaya partial update lebih aman (§5.5 kontrak).
    }
}
