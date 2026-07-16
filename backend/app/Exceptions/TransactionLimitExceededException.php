<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionLimitExceededException extends Exception
{
    private const LIMIT = 800000;

    public function __construct(private readonly float $totalPrice)
    {
        parent::__construct('Total transaksi melebihi batas maksimum Rp 800.000');
    }

    public function render(Request $request): JsonResponse
    {
        $formattedTotal = number_format($this->totalPrice, 0, ',', '.');
        $formattedLimit = number_format(self::LIMIT, 0, ',', '.');

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'quantity' => [
                    "Total harga (Rp {$formattedTotal}) melebihi batas Rp {$formattedLimit} per transaksi",
                ],
            ],
        ], 422);
    }
}
