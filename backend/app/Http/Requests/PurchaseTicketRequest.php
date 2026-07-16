<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:user dijaga di route/middleware
    }

    public function rules(): array
    {
        return [
            'performance_slot_id' => ['required', 'integer', 'exists:performance_slots,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
        // BR-1 (total <= 800K) tidak dicek di sini karena butuh harga slot —
        // divalidasi di TicketPurchaseService sebelum masuk DB::transaction.
    }
}
