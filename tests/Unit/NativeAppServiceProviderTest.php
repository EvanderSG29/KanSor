<?php

use App\Events\OpenTelescopeWindow;
use App\Events\ToggleDebugbarDrawer;
use App\Providers\NativeAppServiceProvider;
use Native\Desktop\Client\Client;
use Native\Desktop\Facades\Window;
use Native\Desktop\Menu\MenuBuilder;
use Native\Desktop\Windows\Window as NativeWindow;
use Tests\TestCase;

uses(TestCase::class);

test('it shares the main sqlite database with nativephp and registers the debug menu', function () {
    config([
        'nativephp-internal.running' => true,
        'database.connections.nativephp.database' => database_path('nativephp.sqlite'),
    ]);

    $client = Mockery::mock(Client::class);
    $client->shouldReceive('post')
        ->once()
        ->with('menu', Mockery::on(function (array $payload): bool {
            $debugMenu = collect($payload['items'] ?? [])
                ->firstWhere('label', 'Debug');

            if (! is_array($debugMenu)) {
                return false;
            }

            $submenu = $debugMenu['submenu']['submenu'] ?? [];

            return collect($submenu)->contains(fn (array $item): bool => ($item['event'] ?? null) === ToggleDebugbarDrawer::class)
                && collect($submenu)->contains(fn (array $item): bool => ($item['event'] ?? null) === OpenTelescopeWindow::class);
        }));

    $this->app->instance(Client::class, $client);
    $this->app->instance(MenuBuilder::class, new MenuBuilder($client));

    $mainWindow = new NativeWindow('main');
    $telescopeWindow = new NativeWindow('telescope-github');
    $windowFake = Window::fake()->alwaysReturnWindows([$mainWindow, $telescopeWindow]);

    app(NativeAppServiceProvider::class)->boot();

    expect(config('database.connections.nativephp.database'))
        ->toBe(database_path('database.sqlite'))
        ->and($mainWindow->webPreferences)
        ->toMatchArray(['webviewTag' => true]);

    $windowFake->assertOpened('main');
});
