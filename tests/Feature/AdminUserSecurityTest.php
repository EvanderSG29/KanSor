<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);

    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
});

test('admin user creation rejects weak passwords', function () {
    $this->actingAs($this->admin)
        ->from(route('kansor.admin.users.create'))
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Lemah',
            'email' => 'petugas-lemah@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.create'))
        ->assertSessionHasErrors('password');
});

test('admin user email is normalized to lowercase when stored', function () {
    $this->actingAs($this->admin)
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Besar',
            'email' => 'PETUGAS.UPPER@EXAMPLE.COM',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.index'));

    $managedUser = User::query()->where('name', 'Petugas Besar')->firstOrFail();

    expect($managedUser->email)->toBe('petugas.upper@example.com');
});

test('admin user email uniqueness is enforced after lowercase normalization', function () {
    User::factory()->create([
        'email' => 'petugas.sama@example.com',
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->actingAs($this->admin)
        ->from(route('kansor.admin.users.create'))
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Duplikat',
            'email' => 'PETUGAS.SAMA@EXAMPLE.COM',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.create'))
        ->assertSessionHasErrors('email');
});

test('last active admin cannot be demoted through the update form', function () {
    $this->actingAs($this->admin)
        ->from(route('kansor.admin.users.edit', $this->admin))
        ->put(route('kansor.admin.users.update', $this->admin), [
            'name' => 'Admin Tunggal',
            'email' => 'admin@example.com',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.edit', $this->admin))
        ->assertSessionHas('error');

    $managedUser = $this->admin->fresh();

    expect($managedUser)->not->toBeNull()
        ->and($managedUser?->role)->toBe(User::ROLE_ADMIN)
        ->and($managedUser?->active)->toBeTrue()
        ->and($managedUser?->status)->toBe(User::STATUS_ACTIVE);
});

test('last active admin cannot be set inactive through the update form', function () {
    $this->actingAs($this->admin)
        ->from(route('kansor.admin.users.edit', $this->admin))
        ->put(route('kansor.admin.users.update', $this->admin), [
            'name' => 'Admin Tunggal',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'active' => '0',
        ])
        ->assertRedirect(route('kansor.admin.users.edit', $this->admin))
        ->assertSessionHas('error');

    $managedUser = $this->admin->fresh();

    expect($managedUser)->not->toBeNull()
        ->and($managedUser?->role)->toBe(User::ROLE_ADMIN)
        ->and($managedUser?->active)->toBeTrue()
        ->and($managedUser?->status)->toBe(User::STATUS_ACTIVE);
});

