<?php

namespace App\Http\Requests;

use App\Models\PerformanceSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'band_id' => [
                'required',
                'integer',
                Rule::exists('bands', 'id')->whereNull('deleted_at'),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_total' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('end_time', 'end_time harus lebih besar dari start_time.');
            }

            // UNIQUE(event_id, band_id, start_time) — BR-9
            $eventId = $this->route('event')?->id ?? $this->route('id');
            $bandId = $this->input('band_id');

            if ($eventId && $bandId && $start) {
                $exists = PerformanceSlot::where('event_id', $eventId)
                    ->where('band_id', $bandId)
                    ->where('start_time', $start)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'band_id',
                        'Band ini sudah punya slot pada jam yang sama di event ini.'
                    );
                }
            }
        });
    }
}
