<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'venue_note' => ['nullable', 'string', 'max:100'],
            'poster' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],

            'slots' => ['required', 'array', 'min:1'],
            'slots.*.band_id' => [
                'required',
                'integer',
                Rule::exists('bands', 'id')->whereNull('deleted_at'),
            ],
            'slots.*.start_time' => ['required', 'date_format:H:i'],
            'slots.*.end_time' => ['required', 'date_format:H:i'],
            'slots.*.price' => ['required', 'numeric', 'min:0'],
            'slots.*.stock_total' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $slots = $this->input('slots', []);
            $seen = [];

            foreach ($slots as $index => $slot) {
                $start = $slot['start_time'] ?? null;
                $end = $slot['end_time'] ?? null;
                $bandId = $slot['band_id'] ?? null;

                if ($start && $end && $end <= $start) {
                    $validator->errors()->add(
                        "slots.{$index}.end_time",
                        'end_time harus lebih besar dari start_time.'
                    );
                }

                // kombinasi (band_id, start_time) unik dalam array slots yang dikirim
                $key = $bandId . '|' . $start;
                if ($bandId && $start) {
                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "slots.{$index}.band_id",
                            'Kombinasi band_id dan start_time duplikat di dalam request ini.'
                        );
                    }
                    $seen[$key] = true;
                }
            }
        });
    }
}
