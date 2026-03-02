<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomingGoodsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.csv')->name('logout');

Route::middleware('auth.csv')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
    Route::post('/produk', [ProductController::class, 'store'])
        ->middleware('role:admin,petugas')
        ->name('produk.store');
    Route::put('/produk/{id}', [ProductController::class, 'update'])
        ->middleware('role:admin,petugas')
        ->name('produk.update');
    Route::delete('/produk/{id}', [ProductController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('produk.destroy');

    Route::get('/transaksi', [TransactionController::class, 'index'])->middleware('role:admin,petugas')->name('transaksi.index');
    Route::post('/transaksi', [TransactionController::class, 'store'])->middleware('role:admin,petugas')->name('transaksi.store');

    Route::get('/barang-masuk', [IncomingGoodsController::class, 'index'])->middleware('role:admin,pemasok')->name('barang-masuk.index');
    Route::post('/barang-masuk', [IncomingGoodsController::class, 'store'])->middleware('role:admin,pemasok')->name('barang-masuk.store');

    Route::get('/laporan', [ReportController::class, 'index'])->middleware('role:admin')->name('laporan.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])->middleware('role:admin')->name('laporan.export');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin')->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('role:admin')->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('role:admin')->name('users.destroy');
});
