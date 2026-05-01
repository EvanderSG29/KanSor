<?php

use App\Events\OpenTelescopeWindow;
use App\Events\ToggleDebugbarDrawer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Native\Desktop\Client\Client;
use Native\Desktop\Facades\Window;
use Native\Desktop\Windows\Window as NativeWindow;

uses(RefreshDatabase::class);

test('toggle debugbar drawer broadcasts on the nativephp channel', function () {
    expect((new ToggleDebugbarDrawer)->broadcastOn())->toBe(['nativephp']);
});

test('desktop telescope route dispatches the telescope event', function () {
    $this->app->detectEnvironment(fn () => 'local');

    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    Event::fake([OpenTelescopeWindow::class]);

    $csrfToken = 'native-desktop-local';

    $this->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->post(route('native.desktop.telescope-window'), [
            '_token' => $csrfToken,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    Event::assertDispatched(OpenTelescopeWindow::class);
});

test('desktop telescope route is forbidden outside local environment', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    Event::fake([OpenTelescopeWindow::class]);

    $csrfToken = 'native-desktop-production';

    $this->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->post(route('native.desktop.telescope-window'), [
            '_token' => $csrfToken,
        ])
        ->assertForbidden();

    Event::assertNotDispatched(OpenTelescopeWindow::class);
});

test('desktop telescope route is forbidden for non admin users', function () {
    $this->app->detectEnvironment(fn () => 'local');

    $user = User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    Event::fake([OpenTelescopeWindow::class]);

    $csrfToken = 'native-desktop-petugas';

    $this->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->post(route('native.desktop.telescope-window'), [
            '_token' => $csrfToken,
        ])
        ->assertForbidden();

    Event::assertNotDispatched(OpenTelescopeWindow::class);
});

test('desktop window control route proxies the requested action', function (string $action, string $method) {
    config(['nativephp-internal.running' => true]);

    Window::shouldReceive($method)
        ->once()
        ->with('main');

    $csrfToken = 'native-desktop-window-control';

    $this->withSession(['_token' => $csrfToken])
        ->post(route('native.desktop.window-control', ['action' => $action]), [
            '_token' => $csrfToken,
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'action' => $action,
        ]);
})->with([
    'minimize' => ['minimize', 'minimize'],
    'maximize' => ['maximize', 'maximize'],
    'reload' => ['reload', 'reload'],
    'close' => ['close', 'close'],
]);

test('desktop window control route is unavailable outside native desktop runtime', function () {
    config(['nativephp-internal.running' => false]);

    Window::shouldReceive('minimize')->never();

    $csrfToken = 'native-desktop-window-control-disabled';

    $this->withSession(['_token' => $csrfToken])
        ->post(route('native.desktop.window-control', ['action' => 'minimize']), [
            '_token' => $csrfToken,
        ])
        ->assertNotFound();
});

test('login page renders the native titlebar only in native desktop runtime', function () {
    config(['nativephp-internal.running' => true]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('aria-label="KanSor native titlebar"', false)
        ->assertSee('data-native-window-control="close"', false)
        ->assertSee('nativeWindowControlUrl', false)
        ->assertSee('__ACTION__', false);

    config(['nativephp-internal.running' => false]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('aria-label="KanSor native titlebar"', false)
        ->assertDontSee('data-native-window-control="close"', false);
});

test('login page uses built assets in native desktop runtime even when vite hot mode is active', function () {
    config(['nativephp-internal.running' => true]);

    $hotFile = public_path('hot');
    $hadHotFile = File::exists($hotFile);
    $originalHotFileContents = $hadHotFile ? File::get($hotFile) : null;

    File::put($hotFile, 'http://[::1]:5173');

    try {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('/build/assets/', false)
            ->assertDontSee('@vite/client', false)
            ->assertDontSee(':5173', false);
    } finally {
        if ($hadHotFile) {
            File::put($hotFile, $originalHotFileContents);
        } else {
            File::delete($hotFile);
        }
    }
});

test('login page keeps vite hot assets for browser runtime when hot mode is active', function () {
    config(['nativephp-internal.running' => false]);

    $hotFile = public_path('hot');
    $hadHotFile = File::exists($hotFile);
    $originalHotFileContents = $hadHotFile ? File::get($hotFile) : null;

    File::put($hotFile, 'http://[::1]:5173');

    try {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('@vite/client', false)
            ->assertSee('http://[::1]:5173/resources/js/app.js', false);
    } finally {
        if ($hadHotFile) {
            File::put($hotFile, $originalHotFileContents);
        } else {
            File::delete($hotFile);
        }
    }
});

test('login page csp allows vite assets from localhost, 127.0.0.1, and ::1', function () {
    config(['nativephp-internal.running' => false]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('script-src &#039;self&#039; &#039;unsafe-inline&#039; http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173;', false)
        ->assertSee('style-src &#039;self&#039; &#039;unsafe-inline&#039; https://fonts.googleapis.com http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173;', false)
        ->assertSee('connect-src &#039;self&#039; http://127.0.0.1:8100 http://localhost:8000 http://127.0.0.1:8000 http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173 ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173;', false);
});

test('login page csp omits vite dev hosts in native desktop runtime', function () {
    config(['nativephp-internal.running' => true]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('script-src &#039;self&#039; &#039;unsafe-inline&#039;;', false)
        ->assertSee('style-src &#039;self&#039; &#039;unsafe-inline&#039; https://fonts.googleapis.com;', false)
        ->assertSee('connect-src &#039;self&#039; http://127.0.0.1:8100 http://localhost:8000 http://127.0.0.1:8000;', false)
        ->assertDontSee(':5173', false);
});

test('dispatching the telescope event opens or focuses the dedicated window', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('post')->atLeast()->times(2);
    $this->app->instance(Client::class, $client);

    $telescopeWindow = new NativeWindow('telescope-github');
    $windowFake = Window::fake()->alwaysReturnWindows([$telescopeWindow]);

    event(new OpenTelescopeWindow);

    $windowFake->assertOpened('telescope-github');

    expect($telescopeWindow->title)->toBe('Laravel Telescope')
        ->and($telescopeWindow->url)->toBe('https://github.com/laravel/telescope')
        ->and($telescopeWindow->preventLeaveDomain)->toBeTrue()
        ->and($telescopeWindow->autoHideMenuBar)->toBeTrue();
});
