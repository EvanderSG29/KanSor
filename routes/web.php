<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NativeDesktopController;
use App\Http\Controllers\PosKantin\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\PosKantin\Admin\CanteenTotalController as AdminCanteenTotalController;
use App\Http\Controllers\PosKantin\Admin\FoodController as AdminFoodController;
use App\Http\Controllers\PosKantin\Admin\SaleController as AdminSaleController;
use App\Http\Controllers\PosKantin\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\PosKantin\Admin\UserController as AdminUserController;
use App\Http\Controllers\PosKantin\PreferenceController;
use App\Http\Controllers\PosKantin\ReportController;
use App\Http\Controllers\PosKantin\SaleController as LocalSaleController;
use App\Http\Controllers\PosKantin\SavingController;
use App\Http\Controllers\PosKantin\SupplierController;
use App\Http\Controllers\PosKantin\SupplierPayoutController;
use App\Http\Controllers\PosKantin\SyncController;
use App\Http\Controllers\PosKantin\TransactionController;
use App\Http\Controllers\PosKantin\UserController;
use App\Http\Controllers\Setup\SchemaReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/_setup/schema-readiness', [SchemaReadinessController::class, 'status'])->name('setup.status');
Route::post('/_setup/run-migrations', [SchemaReadinessController::class, 'runMigrations'])->name('setup.run-migrations');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes([
    'register' => false,
]);

Route::post('/native/desktop/window/{action}', [NativeDesktopController::class, 'controlWindow'])
    ->where('action', 'minimize|maximize|reload|close')
    ->name('native.desktop.window-control');

Route::middleware('auth')->group(function () {
    Route::post('/native/desktop/telescope-window', [NativeDesktopController::class, 'openTelescopeWindow'])
        ->name('native.desktop.telescope-window');
});


Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::redirect('/pos-kantin', '/kansor', 301);
    Route::get('/pos-kantin/{path}', function (string $path) {
        return redirect('/kansor/'.$path, 301);
    })->where('path', '.*');
});

Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('kansor')->name('pos-kantin.')->group(function () {
        Route::get('/transaksi', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/simpanan', [SavingController::class, 'index'])->name('savings.index');
        Route::get('/pemasok', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/pembayaran', [SupplierPayoutController::class, 'index'])->name('supplier-payouts.index');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/sinkronisasi', [SyncController::class, 'index'])->name('sync.index');
        Route::get('/sinkronisasi/status', [SyncController::class, 'status'])->name('sync.status');
        Route::post('/sinkronisasi/auto', [SyncController::class, 'auto'])
            ->middleware('throttle:sync-auto')
            ->name('sync.auto');
        Route::post('/sinkronisasi/jalankan', [SyncController::class, 'run'])->name('sync.run');
        Route::post('/sinkronisasi/jalankan-terpilih', [SyncController::class, 'runSelected'])->name('sync.run-selected');
        Route::post('/sinkronisasi/retry', [SyncController::class, 'retryFailed'])->name('sync.retry');
        Route::post('/sinkronisasi/outbox/{outboxId}/discard', [SyncController::class, 'discard'])->name('sync.outbox.discard');
        Route::post('/sinkronisasi/outbox/{outboxId}/resend', [SyncController::class, 'resend'])->name('sync.outbox.resend');

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
            Route::resource('users', AdminUserController::class)->except('show');
            Route::resource('suppliers', AdminSupplierController::class)->except('show');
            Route::resource('foods', AdminFoodController::class)->except('show');
            Route::resource('sales', AdminSaleController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
            Route::resource('canteen-totals', AdminCanteenTotalController::class)->only(['index']);

            Route::patch('sales/{sale}/confirm-supplier-paid', [AdminSaleController::class, 'confirmSupplierPaid'])
                ->name('sales.confirm-supplier-paid');
            Route::patch('sales/{sale}/confirm-canteen-deposited', [AdminSaleController::class, 'confirmCanteenDeposited'])
                ->name('sales.confirm-canteen-deposited');
        });

        Route::resource('sales', LocalSaleController::class);
        Route::resource('preferences', PreferenceController::class)->only(['index', 'store', 'update']);
    });
});
