<?php

namespace App\Providers;

use App\Events\OpenTelescopeWindow;
use App\Listeners\OpenTelescopeWindowListener;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
