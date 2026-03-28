<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagementKendaraan;
use App\Http\Controllers\PeramalanTesController;
use App\Http\Controllers\PerbandinganController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransaksiPenyewaanController;

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    // Management Kendaraan
    Route::get('/management-kendaraan', [ManagementKendaraan::class, 'index'])->name('management_kendaraan.index');
    Route::post('/management-kendaraan', [ManagementKendaraan::class, 'store'])->name('management_kendaraan.store');
    Route::put('/management-kendaraan/{id}', [ManagementKendaraan::class, 'update'])->name('management_kendaraan.update');
    Route::delete('/management-kendaraan/{id}', [ManagementKendaraan::class, 'destroy'])->name('management_kendaraan.destroy');

    // Transaksi Penyewaan
    Route::get('/transaksi-penyewaan/dikembalikan-data', [TransaksiPenyewaanController::class, 'getDikembalikanData'])->name('transaksi_penyewaan.dikembalikan_data');
    Route::get('/transaksi-penyewaan', [TransaksiPenyewaanController::class, 'index'])->name('transaksi_penyewaan.index');
    Route::post('/transaksi-penyewaan', [TransaksiPenyewaanController::class, 'store'])->name('transaksi_penyewaan.store');
    Route::post('/transaksi-penyewaan/kembalikan-form', [TransaksiPenyewaanController::class, 'kembalikanForm'])->name('transaksi_penyewaan.kembalikan.form');
    Route::post('/transaksi-penyewaan/{id}/kembalikan', [TransaksiPenyewaanController::class, 'kembalikan'])->name('transaksi_penyewaan.kembalikan');
    Route::put('/transaksi-penyewaan/{id}', [TransaksiPenyewaanController::class, 'update'])->name('transaksi_penyewaan.update');
    Route::delete('/transaksi-penyewaan/{id}', [TransaksiPenyewaanController::class, 'destroy'])->name('transaksi_penyewaan.destroy');

    // Peramalan TES
    Route::get('/peramalan-tes', [PeramalanTesController::class, 'index'])->name('peramalan_tes.index');
    Route::post('/peramalan-tes', [PeramalanTesController::class, 'process'])->name('peramalan_tes.process');
    Route::post('/peramalan-tes/store', [PeramalanTesController::class, 'store'])->name('peramalan_tes.store');
    Route::get('/peramalan-tes/riwayat', [PeramalanTesController::class, 'riwayat'])->name('peramalan_tes.riwayat');
    Route::get('/peramalan-tes/export-pdf/{id}', [PeramalanTesController::class, 'exportPdf'])->name('peramalan_tes.export_pdf');
    Route::delete('/peramalan-tes/{id}', [PeramalanTesController::class, 'destroy'])->name('peramalan_tes.destroy');

    // Perbandingan TES vs SMA
    Route::get('/perbandingan', [PerbandinganController::class, 'index'])->name('perbandingan.index');
    Route::get('/perbandingan/{id}', [PerbandinganController::class, 'show'])->name('perbandingan.show');
    Route::get('/perbandingan/{id}/compare', [PerbandinganController::class, 'compare'])->name('perbandingan.compare');
    Route::delete('/perbandingan/{id}', [PerbandinganController::class, 'destroy'])->name('perbandingan.destroy');

});
