<?php

use App\Models\User;
use App\Services\PosKantin\UserSyncPayloadFactory;

test('user sync payload maps local user into apps script payload with derived nickname', function () {
    $user = new User([
        'name' => 'Petugas Baru',
        'email' => 'petugas-baru@example.com',
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
    $user->id = 42;

    $payload = app(UserSyncPayloadFactory::class)->make($user, 'rahasia123');

    expect($payload)->toMatchArray([
        'id' => '42',
        'fullName' => 'Petugas Baru',
        'nickname' => 'Petugas',
        'email' => 'petugas-baru@example.com',
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'classGroup' => '',
        'notes' => '',
        'password' => 'rahasia123',
    ]);
});

test('user sync payload falls back to email local part when full name has no nickname token', function () {
    $user = new User([
        'name' => '   ',
        'email' => 'evander@example.com',
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
    $user->id = 99;

    $payload = app(UserSyncPayloadFactory::class)->make($user);

    expect($payload)->toMatchArray([
        'id' => '99',
        'fullName' => 'evander',
        'nickname' => 'evander',
        'email' => 'evander@example.com',
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'classGroup' => '',
        'notes' => '',
    ])->not->toHaveKey('password');
});
