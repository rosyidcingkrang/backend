<?php

use App\Exceptions\Handler as ApiHandler;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckTokenExpiry;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias dipakai di routes/api.php: 'auth:sanctum' (bawaan Sanctum)
        // + 'token.expiry' (§1.3 kontrak) + 'role.admin' (§1.4 kontrak, untuk
        // semua route /api/admin/*).
        $middleware->alias([
            'token.expiry' => CheckTokenExpiry::class,
            'role.admin' => CheckAdminRole::class,
        ]);

        // Illuminate\Http\Middleware\HandleCors sudah otomatis ada di stack
        // global bawaan Laravel — cukup atur allowed origin di config/cors.php,
        // tidak perlu didaftarkan manual di sini (§1 Persiapan Bersama panduan).
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Checklist backend: SEMUA response API (termasuk error 500) wajib
        // format {success, message[, errors]} persis kontrak §1.1 — jangan
        // biarkan halaman/JSON default Laravel bocor ke frontend. Logic
        // lengkapnya sudah ditulis di App\Exceptions\Handler::render(),
        // di sini cukup didelegasikan.
        $exceptions->render(function (Throwable $e, Request $request) {
            return (new ApiHandler(app()))->render($request, $e);
        });
    })->create();
