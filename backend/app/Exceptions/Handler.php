<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // Exception kustom (InsufficientStockException, TransactionLimitExceededException,
        // BandStillLiveException) sudah punya render() sendiri dan otomatis dipakai Laravel,
        // jadi tidak perlu didaftarkan di sini.
    }

    /**
     * Semua response untuk request API wajib format {success, message[, errors]}
     * sesuai kontrak §1.1 — termasuk error 500, tidak boleh bocorkan halaman/JSON
     * default Laravel maupun stack trace.
     */
    public function render($request, Throwable $e): mixed
    {
        if ($this->isApiRequest($request)) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private function renderApiException(Request $request, Throwable $e): JsonResponse
    {
        // Exception yang sudah tahu cara render dirinya sendiri (409/422 kustom di atas)
        if (method_exists($e, 'render')) {
            $rendered = $e->render($request);
            if ($rendered instanceof JsonResponse) {
                return $rendered;
            }
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Terjadi kesalahan pada request',
            ], $e->getStatusCode());
        }

        // Fallback: error tak terduga -> 500, pesan generik, tidak bocorkan detail internal.
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server',
        ], 500);
    }
}
