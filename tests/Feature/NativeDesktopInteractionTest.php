<?php

use App\Events\OpenTelescopeWindow;
use App\Events\ToggleDebugbarDrawer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Client\Client;
use Native\Desktop\Facades\Window;
use Native\Desktop\Windows\Window as NativeWindow;

uses(RefreshDatabase::class);

test('toggle debugbar drawer broadcasts on the nativephp channel', function () {
    expect((new ToggleDebugbarDrawer)->broadcastOn())->toBe(['nativephp']);
});

test('desktop telescope route dispatches the telescope event', function () {
    $user = User::factory()->create();

    Event::fake([OpenTelescopeWindow::class]);

    $this->actingAs($user)
        ->postJson(route('native.desktop.telescope-window'))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    Event::assertDispatched(OpenTelescopeWindow::class);
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
