<?php

namespace App\Providers;

use App\Events\OpenTelescopeWindow;
use App\Listeners\OpenTelescopeWindowListener;
use App\Services\PosKantin\PosKantinSyncService;
use App\Services\Setup\SchemaReadinessService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SchemaReadinessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('sync-auto', function (Request $request): Limit {
            $syncIntervalSeconds = max(1, (int) config('services.pos_kantin.sync_interval_seconds', 60));
            $attemptsPerMinute = max(1, (int) ceil(60 / $syncIntervalSeconds));
            $limiterKey = $request->user()?->getAuthIdentifier() !== null
                ? 'sync-auto:user:'.$request->user()->getAuthIdentifier()
                : 'sync-auto:ip:'.$request->ip();

            return Limit::perMinute($attemptsPerMinute)
                ->by($limiterKey)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permintaan sinkronisasi otomatis terlalu sering. Tunggu sebentar lalu coba lagi.',
                    ], 429, $headers);
                });
        });

        Event::listen(
            OpenTelescopeWindow::class,
            OpenTelescopeWindowListener::class,
        );

        View::composer('layouts.app', function ($view): void {
            if (! Auth::check() || ! Schema::hasTable('pos_sync_runs')) {
                return;
            }

            $view->with('kanSorSyncNavigationStatus', app(PosKantinSyncService::class)->statusForUser(Auth::user()));
        });
    }
}
