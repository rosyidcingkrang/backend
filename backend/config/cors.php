<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | §1 Panduan Pengerjaan (Persiapan Bersama): backend dev wajib jalankan
    | php artisan serve dengan CORS diaktifkan untuk origin frontend (mis.
    | http://localhost:5500 kalau pakai Live Server) di awal, supaya frontend
    | dev tidak stuck saat pertama kali fetch.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Sesuaikan/tambah origin di .env (CORS_ALLOWED_ORIGINS, dipisah koma)
    // kalau frontend dev pakai port/tools lain (Live Server default 5500,
    // beberapa setup pakai 5173/3000/8080 dsb).
    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:5500,http://127.0.0.1:5500'
        ))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Token dikirim lewat header Authorization: Bearer (bukan cookie session
    // Sanctum SPA) — jadi credentials/cookie cross-origin tidak dibutuhkan.
    'supports_credentials' => false,

];
