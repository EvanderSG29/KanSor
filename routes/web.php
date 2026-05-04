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

    $registerKanSorRoutes = function (string $namePrefix): void {
        Route::get('/transaksi', [TransactionController::class, 'index'])->name($namePrefix.'transactions.index');
        Route::get('/simpanan', [SavingController::class, 'index'])->name($namePrefix.'savings.index');
        Route::get('/pemasok', [SupplierController::class, 'index'])->name($namePrefix.'suppliers.index');
        Route::get('/pembayaran', [SupplierPayoutController::class, 'index'])->name($namePrefix.'supplier-payouts.index');
        Route::get('/laporan', [ReportController::class, 'index'])->name($namePrefix.'reports.index');
        Route::get('/pengguna', [UserController::class, 'index'])->name($namePrefix.'users.index');
        Route::get('/sinkronisasi', [SyncController::class, 'index'])->name($namePrefix.'sync.index');
        Route::get('/sinkronisasi/status', [SyncController::class, 'status'])->name($namePrefix.'sync.status');
        Route::post('/sinkronisasi/auto', [SyncController::class, 'auto'])->middleware('throttle:sync-auto')->name($namePrefix.'sync.auto');
        Route::post('/sinkronisasi/jalankan', [SyncController::class, 'run'])->name($namePrefix.'sync.run');
        Route::post('/sinkronisasi/jalankan-terpilih', [SyncController::class, 'runSelected'])->name($namePrefix.'sync.run-selected');
        Route::post('/sinkronisasi/retry', [SyncController::class, 'retryFailed'])->name($namePrefix.'sync.retry');
        Route::post('/sinkronisasi/outbox/{outboxId}/discard', [SyncController::class, 'discard'])->name($namePrefix.'sync.outbox.discard');
        Route::post('/sinkronisasi/outbox/{outboxId}/resend', [SyncController::class, 'resend'])->name($namePrefix.'sync.outbox.resend');

        Route::prefix('admin')->name($namePrefix.'admin.')->middleware('role:admin')->group(function () use ($namePrefix): void {
            Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
            Route::resource('users', AdminUserController::class)->except('show')->names($namePrefix.'admin.users');
            Route::resource('suppliers', AdminSupplierController::class)->except('show')->names($namePrefix.'admin.suppliers');
            Route::resource('foods', AdminFoodController::class)->except('show')->names($namePrefix.'admin.foods');
            Route::resource('sales', AdminSaleController::class)->only(['index', 'show', 'edit', 'update', 'destroy'])->names($namePrefix.'admin.sales');
            Route::resource('canteen-totals', AdminCanteenTotalController::class)->only(['index'])->names($namePrefix.'admin.canteen-totals');
            Route::patch('sales/{sale}/confirm-supplier-paid', [AdminSaleController::class, 'confirmSupplierPaid'])->name('sales.confirm-supplier-paid');
            Route::patch('sales/{sale}/confirm-canteen-deposited', [AdminSaleController::class, 'confirmCanteenDeposited'])->name('sales.confirm-canteen-deposited');
        });

        Route::resource('sales', LocalSaleController::class)->names($namePrefix.'sales');
        Route::resource('preferences', PreferenceController::class)->only(['index', 'store', 'update'])->names($namePrefix.'preferences');
    };

    Route::prefix('kansor')->name('kansor.')->group(fn () => $registerKanSorRoutes('kansor.'));
});

