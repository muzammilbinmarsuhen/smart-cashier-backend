<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // =======================
    // NANTI DI SINI ROUTE PRODUK, TRANSAKSI, LAPORAN
    // =======================

    // contoh:
    // Route::apiResource('products', ProductController::class);

});