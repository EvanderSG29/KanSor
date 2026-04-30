<?php

use App\Models\User;
use App\Services\Setup\SchemaReadinessService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schemaReadyAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

function schemaReadyPetugas(): User
{
    return User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('setup blocker is rendered when local schema is not ready', function () {
    $this->app['env'] = 'local';

    $schemaReadinessService = Mockery::mock(SchemaReadinessService::class);
    $schemaReadinessService->shouldReceive('shouldBlockApplication')->once()->andReturnTrue();
    $schemaReadinessService->shouldReceive('status')->once()->andReturn([
        'isLocal' => true,
        'hasPendingMigrations' => true,
        'pendingMigrations' => [
            '2026_04_29_032656_add_active_to_users_table',
            '2026_04_29_032657_create_suppliers_table',
        ],
    ]);
    $schemaReadinessService->shouldReceive('hasPendingMigrations')->andReturnTrue();

    $this->app->instance(SchemaReadinessService::class, $schemaReadinessService);

    $this->actingAs(schemaReadyAdmin())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Setup database lokal diperlukan')
        ->assertSee('2026_04_29_032656_add_active_to_users_table')
        ->assertDontSee('Dashboard POS');
});

test('setup action runs migrations in local environment', function () {
    $this->app['env'] = 'local';
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $schemaReadinessService = Mockery::mock(SchemaReadinessService::class);
    $schemaReadinessService->shouldReceive('runPendingMigrations')->once()->andReturn([
        'success' => true,
        'message' => 'Migrasi lokal berhasil dijalankan.',
        'exitCode' => 0,
        'output' => '',
        'pendingMigrations' => [],
    ]);

    $this->app->instance(SchemaReadinessService::class, $schemaReadinessService);

    $this->postJson(route('setup.run-migrations'))
        ->assertSuccessful()
        ->assertJsonPath('data.success', true)
        ->assertJsonPath('data.pendingMigrations', []);
});

test('setup endpoints are hidden outside local environment', function () {
    $this->getJson(route('setup.status'))->assertNotFound();
    $this->postJson(route('setup.run-migrations'))->assertNotFound();
});

test('admin can access local user index when schema is ready', function () {
    $this->actingAs(schemaReadyAdmin())
        ->get(route('pos-kantin.admin.users.index'))
        ->assertSuccessful()
        ->assertSee('Daftar pengguna')
        ->assertSee('Tambah pengguna');
});

test('petugas still receives forbidden on admin user index when schema is ready', function () {
    $this->actingAs(schemaReadyPetugas())
        ->get(route('pos-kantin.admin.users.index'))
        ->assertForbidden();
});
