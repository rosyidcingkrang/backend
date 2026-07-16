<?php

use App\Http\Controllers\Api\Admin\AdminBandController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminSlotController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| §6 Ringkasan Endpoint — HakoStar API
|--------------------------------------------------------------------------
| Base URL dev: http://localhost:8000/api (prefix "api" sudah otomatis
| ditambahkan oleh RouteServiceProvider / bootstrap/app.php withRouting()).
*/

// ---------------------------------------------------------------------
// 5.1 Auth — Publik
// ---------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ---------------------------------------------------------------------
// 5.3 Konser — Publik (boleh diakses tanpa token)
// ---------------------------------------------------------------------
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

// ---------------------------------------------------------------------
// Semua route di bawah ini butuh token valid & belum expire (§1.3).
// 'auth:sanctum' -> validasi token ada & valid (401 kalau tidak).
// 'token.expiry' -> cek expire 2 jam fix, balas 401 SESSION_EXPIRED (§1.3).
// ---------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'token.expiry'])->group(function () {

    // 5.1 (lanjutan) — Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // 5.2 User — Profil (role: user & admin)
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/profile', [ProfileController::class, 'update']);
    Route::post('/me/avatar', [ProfileController::class, 'uploadAvatar']);

    // 5.4 Tiket (role: user)
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/history', [TicketController::class, 'history']);
    Route::get('/tickets/{id}/download', [TicketController::class, 'download']);

    // -------------------------------------------------------------
    // 5.5 / 5.5b / 5.6 / 5.7 Admin — butuh role admin (§1.4)
    // -------------------------------------------------------------
    Route::middleware('role.admin')->prefix('admin')->group(function () {

        // 5.5 Manajemen Event
        Route::get('/events', [AdminEventController::class, 'index']);
        Route::post('/events', [AdminEventController::class, 'store']);
        Route::put('/events/{id}', [AdminEventController::class, 'update']);
        Route::delete('/events/{id}', [AdminEventController::class, 'destroy']);

        // 5.5b Upload poster event
        Route::post('/events/{id}/poster', [AdminEventController::class, 'uploadPoster']);

        // 5.5 Slot (nested di bawah event untuk create, mandiri untuk update/delete)
        // Route-model-binding: {event} -> Event, {slot} -> PerformanceSlot
        // (nama param HARUS "event"/"slot" persis karena dipakai di
        // StoreSlotRequest/UpdateSlotRequest::route()).
        Route::post('/events/{event}/slots', [AdminSlotController::class, 'store']);
        Route::put('/slots/{slot}', [AdminSlotController::class, 'update']);
        Route::delete('/slots/{slot}', [AdminSlotController::class, 'destroy']);

        // 5.6 Manajemen Band
        // Route-model-binding: {band} -> Band (SoftDeletes, default scope otomatis
        // exclude yang sudah dihapus).
        Route::get('/bands', [AdminBandController::class, 'index']);
        Route::post('/bands', [AdminBandController::class, 'store']);
        Route::put('/bands/{band}', [AdminBandController::class, 'update']);
        Route::delete('/bands/{band}', [AdminBandController::class, 'destroy']);

        // 5.5b Upload logo band
        Route::post('/bands/{band}/logo', [AdminBandController::class, 'uploadLogo']);

        // 5.7 Manajemen User
        // Route-model-binding: {user} -> User (SoftDeletes, default scope otomatis
        // exclude yang sudah dihapus).
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });
});
