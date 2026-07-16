<?php

namespace App\Http\Requests;

use App\Models\PerformanceSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'band_id' => [
                'sometimes',
                'integer',
                Rule::exists('bands', 'id')->whereNull('deleted_at'),
            ],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock_total' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var PerformanceSlot|null $slot */
            $slot = $this->route('slot');

            if (! $slot) {
                return;
            }

            // stock_total tidak boleh diubah lebih kecil dari stock_sold saat ini
            if ($this->has('stock_total') && (int) $this->input('stock_total') < $slot->stock_sold) {
                $validator->errors()->add(
                    'stock_total',
                    "stock_total tidak boleh lebih kecil dari stock_sold saat ini ({$slot->stock_sold})."
                );
            }

            $start = $this->input('start_time', $slot->start_time);
            $end = $this->input('end_time', $slot->end_time);

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('end_time', 'end_time harus lebih besar dari start_time.');
            }
        });
    }
}
