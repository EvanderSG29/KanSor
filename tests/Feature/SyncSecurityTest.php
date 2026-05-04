<?php

use App\Models\PosKantinSyncRun;
use App\Models\User;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kansor.api_url' => 'https://example.test/macros/s/api/exec',
        'services.kansor.sync_interval_seconds' => 60,
    ]);

    Http::preventStrayRequests();
});

function syncSecurityUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('auto sync route is rate limited per authenticated user', function () {
    $user = syncSecurityUser();
    $syncService = Mockery::mock(PosKantinSyncService::class);
    $syncService->shouldReceive('sync')
        ->once()
        ->withArgs(fn (User $resolvedUser, string $trigger): bool => $resolvedUser->is($user) && $trigger === 'auto')
        ->andReturn([
            'ok' => true,
            'runId' => 1,
            'summary' => [],
        ]);

    $this->app->instance(PosKantinSyncService::class, $syncService);

    $this->actingAs($user)
        ->postJson(route('kansor.sync.auto'))
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->actingAs($user)
        ->postJson(route('kansor.sync.auto'))
        ->assertStatus(429)
        ->assertJson([
            'success' => false,
            'message' => 'Permintaan sinkronisasi otomatis terlalu sering. Tunggu sebentar lalu coba lagi.',
        ]);
});

test('sync service returns a friendly error when another sync lock is active', function () {
    $user = syncSecurityUser();
    $lock = Cache::lock('pos-sync:user:'.$user->getKey(), 120);

    expect($lock->get())->toBeTrue();

    try {
        $result = app(PosKantinSyncService::class)->sync($user, 'manual');

        expect($result)->toMatchArray([
            'ok' => false,
            'category' => 'locked',
            'message' => 'Sinkronisasi untuk akun ini sedang berjalan. Tunggu beberapa detik lalu coba lagi.',
        ])->and(PosKantinSyncRun::query()->count())->toBe(0);

        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

test('manual sync route shows the lock message to the user', function () {
    $user = syncSecurityUser();
    $syncService = Mockery::mock(PosKantinSyncService::class);
    $syncService->shouldReceive('sync')
        ->once()
        ->withArgs(fn (User $resolvedUser, string $trigger): bool => $resolvedUser->is($user) && $trigger === 'manual')
        ->andReturn([
            'ok' => false,
            'category' => 'locked',
            'message' => 'Sinkronisasi untuk akun ini sedang berjalan. Tunggu beberapa detik lalu coba lagi.',
        ]);

    $this->app->instance(PosKantinSyncService::class, $syncService);

    $this->actingAs($user)
        ->from(route('kansor.sync.index'))
        ->post(route('kansor.sync.run'))
        ->assertRedirect(route('kansor.sync.index'))
        ->assertSessionHas('error', 'Sinkronisasi untuk akun ini sedang berjalan. Tunggu beberapa detik lalu coba lagi.');
});

