<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BandStillLiveException extends Exception
{
    public function __construct(private readonly string $bandName)
    {
        parent::__construct("{$bandName} sedang tampil saat ini dan tidak bisa dihapus.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 409);
    }
}
