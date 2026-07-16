<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(private readonly int $stockAvailable)
    {
        parent::__construct("Stok tiket tidak mencukupi. Sisa {$stockAvailable} tiket.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 409);
    }
}
