<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can register', function () {
    $response = $this->post('/register', [
        'name' => 'Ivan Marigib',
        'email' => 'ivanmarigib@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()
        ->where('email', 'ivanmarigib@gmail.com')
        ->firstOrFail();

    $this->assertModelExists($user);
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');
});
