<?php

namespace App\Providers;

use App\Events\OpenTelescopeWindow;
use App\Events\ToggleDebugbarDrawer;
use Illuminate\Support\Facades\DB;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    private const MAIN_WINDOW_ID = 'main';

    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $this->configureSharedNativeDatabase();
        $this->openMainWindow();
        $this->registerNativeMenu();
    }

    private function configureSharedNativeDatabase(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        config([
            'database.connections.nativephp.database' => database_path('database.sqlite'),
        ]);

        DB::purge('nativephp');
        DB::reconnect('nativephp');
    }

    private function openMainWindow(): void
    {
        Window::open(self::MAIN_WINDOW_ID)
            ->title(config('app.name', 'KanSor'))
            ->frameless()
            ->hideMenu()
            ->backgroundColor('#20272b')
            ->width((int) config('nativephp.window.width', 1440))
            ->height((int) config('nativephp.window.height', 960))
            ->minWidth((int) config('nativephp.window.min_width', 1024))
            ->minHeight((int) config('nativephp.window.min_height', 720))
            ->webPreferences([
                'webviewTag' => true,
            ])
            ->when(
                (bool) config('nativephp.window.remember_state', true),
                fn ($window) => $window->rememberState()
            );
    }

    private function registerNativeMenu(): void
    {
        Menu::create(
            Menu::app(),
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::label('Debug')->submenu(
                Menu::label('Toggle Fruitcake Debugbar Drawer (Ctrl+Shift+D, lalu F)')
                    ->event(ToggleDebugbarDrawer::class),
                Menu::label('Laravel Telescope Repo (Ctrl+Shift+D)')
                    ->event(OpenTelescopeWindow::class),
            ),
            Menu::window(),
        );
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
