<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\ProyekController;
use App\Http\Controllers\Api\KontrakController;
use App\Http\Controllers\Api\BahanApiController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\BahanMasukController;
use App\Http\Controllers\Api\JenisBahanController;
use App\Http\Controllers\Api\BahanSearchController;
use App\Http\Controllers\API\DashboardApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('dashboard')->controller(DashboardApiController::class)->group(function () {
        Route::get('/statistics', 'getStatistics');
        Route::get('/pengajuan', 'getPendingPengajuan');
        Route::get('/bahan-sisa-terbanyak', 'getBahanSisaTerbanyak');
        Route::get('/bahan-sisa-tersedikit', 'getBahanSisaPalingSedikit');
        Route::get('/produksi-proses', 'getProduksiProses');
        Route::get('/projek-proses', 'getProjekProses');
        Route::get('/projek-rnd-proses', 'getProjekRndProses');
        Route::get('/chart', 'getChartData');
        Route::get('/bahan-setengah-jadi', 'getBahanSetengahJadi');
        Route::get('/sisa-stok-bahan', 'getSisaStokBahan');
    });

    Route::get('/bahans', [BahanApiController::class, 'index']);
    Route::get('/bahans/create', [BahanApiController::class, 'create']);
    Route::post('/bahans', [BahanApiController::class, 'store']);
    Route::get('/bahans/{id}', [BahanApiController::class, 'show']);
    Route::put('/bahans/{id}', [BahanApiController::class, 'update']);
    Route::delete('/bahans/{id}', [BahanApiController::class, 'destroy']);
    Route::get('/bahan-export', [BahanApiController::class, 'export']);

    Route::prefix('jenisbahan')->group(function () {
        Route::get('/', [JenisBahanController::class, 'index']);        // Get semua data
        Route::post('/', [JenisBahanController::class, 'store']);       // Tambah data
        Route::put('/{id}', [JenisBahanController::class, 'update']);   // Update data
        Route::delete('/{id}', [JenisBahanController::class, 'destroy']);// Delete data
    });

    Route::prefix('unit')->group(function () {
        Route::get('/', [UnitController::class, 'index']);        // Get semua data
        Route::post('/', [UnitController::class, 'store']);       // Tambah data
        Route::put('/{id}', [UnitController::class, 'update']);   // Update data
        Route::delete('/{id}', [UnitController::class, 'destroy']);// Delete data
    });

    Route::prefix('supplier')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);        // Get semua data
        Route::post('/', [SupplierController::class, 'store']);       // Tambah data
        Route::put('/{id}', [SupplierController::class, 'update']);   // Update data
        Route::delete('/{id}', [SupplierController::class, 'destroy']);// Delete data
    });

    Route::prefix('kontrak')->group(function () {
        Route::get('/', [KontrakController::class, 'index']);        // Get semua data
        Route::post('/', [KontrakController::class, 'store']);       // Tambah data
        Route::put('/{id}', [KontrakController::class, 'update']);   // Update data
        Route::delete('/{id}', [KontrakController::class, 'destroy']);// Delete data
    });


    Route::get('/bahan/search', [BahanSearchController::class, 'index']);

    Route::get('/bahan-masuk', [BahanMasukController::class, 'index']);
    Route::post('/bahan-masuk', [BahanMasukController::class, 'store']);
    Route::get('/bahan-masuk-export', [BahanMasukController::class, 'export']);

    Route::get('/proyeks', [ProyekController::class, 'index']);



    Route::post('/logout', [AuthController::class, 'logout']);
});

