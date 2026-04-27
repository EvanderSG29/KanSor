<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NativeDesktopController;
use App\Http\Controllers\PosKantin\ReportController;
use App\Http\Controllers\PosKantin\SavingController;
use App\Http\Controllers\PosKantin\SupplierController;
use App\Http\Controllers\PosKantin\SupplierPayoutController;
use App\Http\Controllers\PosKantin\TransactionController;
use App\Http\Controllers\PosKantin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : view('/auth/login');
});

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/native/desktop/telescope-window', [NativeDesktopController::class, 'openTelescopeWindow'])
        ->name('native.desktop.telescope-window');

    Route::prefix('pos-kantin')->name('pos-kantin.')->group(function () {
        Route::get('/transaksi', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/simpanan', [SavingController::class, 'index'])->name('savings.index');
        Route::get('/pemasok', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/pembayaran', [SupplierPayoutController::class, 'index'])->name('supplier-payouts.index');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    });
});
