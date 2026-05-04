<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);
});

test('a guest cannot submit the public register form', function () {
    $this->post('/register', [
        'name' => 'Guest Register',
        'email' => 'guest-register@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->where('email', 'guest-register@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

test('the login page does not show the register link', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('Register a new account');
});

test('admin can still create users from the internal admin module', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Internal',
            'email' => 'petugas-internal@example.com',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.index'));

    $user = User::query()->where('email', 'petugas-internal@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user?->role)->toBe(User::ROLE_PETUGAS)
        ->and($user?->active)->toBeTrue()
        ->and(Hash::check('KanSor!Pass123', (string) $user?->password))->toBeTrue();
});

