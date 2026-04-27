<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('it logs in using configured pos kantin admin credentials and provisions a local user', function () {
    config([
        'services.pos_kantin.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.pos_kantin.admin_password' => '12345678',
    ]);

    $response = $this->post('/login', [
        'email' => 'evandersmidgidiin@gmail.com',
        'password' => '12345678',
    ]);

    $user = User::query()
        ->where('email', 'evandersmidgidiin@gmail.com')
        ->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');

    expect(Hash::check('12345678', $user->password))->toBeTrue()
        ->and($user->name)->toBe('Evandersmidgidiin');
});

test('it syncs the local password for the configured pos kantin admin user', function () {
    config([
        'services.pos_kantin.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.pos_kantin.admin_password' => '87654321',
    ]);

    $user = User::factory()->create([
        'email' => 'evandersmidgidiin@gmail.com',
        'password' => 'old-password',
        'name' => 'Evander',
    ]);

    $response = $this->post('/login', [
        'email' => 'evandersmidgidiin@gmail.com',
        'password' => '87654321',
    ]);

    $user->refresh();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');

    expect(Hash::check('87654321', $user->password))->toBeTrue()
        ->and($user->name)->toBe('Evander');
});
