<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\KategoriController;
use App\Http\Controllers\API\AlatController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PeminjamanController;
use App\Http\Controllers\API\PengembalianController;
use App\Http\Controllers\API\LogAktivitasController;
use App\Http\Controllers\API\LaporanController;

// Public Routes (Tidak perlu token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Wajib membawa Bearer Token dari Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route untuk hak akses admin
    Route::middleware('role.admin')->group(function () {
        Route::apiResource('kategori', KategoriController::class); 
        Route::apiResource('alat', AlatController::class);
        Route::get('/katalog', [AlatController::class, 'katalog']);
        Route::apiResource('users', UserController::class);

        // Route peminjaman untuk Admin
        Route::get('/peminjaman', [PeminjamanController::class, 'index']);
        Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show']);
        Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve']);
        Route::put('/peminjaman/{peminjaman}', [PeminjamanController::class, 'update']);
        Route::delete('/peminjaman/{peminjaman}', [PeminjamanController::class, 'destroy']);

        // Route pengembalian untuk Admin
        Route::get('/pengembalian', [PengembalianController::class, 'index']);
        Route::get('/pengembalian/{pengembalian}', [PengembalianController::class, 'show']);
        Route::put('/pengembalian/{pengembalian}', [PengembalianController::class, 'update']);
        Route::delete('/pengembalian/{pengembalian}', [PengembalianController::class, 'destroy']);

        // Route Log-aktivitas untuk Admin
        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);

        // Route Laporan untuk Admin
        Route::get('/laporan-peminjaman', [LaporanController::class, 'index']);
    });

    // Route untuk hak akses petugas
    Route::middleware('role.petugas')->group(function () {

        // Route peminjaman untuk Petugas
        Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve']);

        // Route pengembalian untuk Petugas
        Route::post('/pengembalian', [PengembalianController::class, 'store']);

        // Route Laporan untuk Petugas
        Route::get('/laporan-peminjaman', [LaporanController::class, 'index']);
    });

    // Route untuk hak akses peminjam
    Route::middleware('role.peminjam')->group(function () {
        Route::get('/katalog', [AlatController::class, 'katalog']);
        Route::post('/peminjaman', [PeminjamanController::class, 'store']);
        Route::get('/riwayat-pinjam', [PeminjamanController::class, 'riwayat']);
    });

    // ROUTE PENGEMBALIAN (Semua Role)
    Route::apiResource('pengembalian', PengembalianController::class);
});