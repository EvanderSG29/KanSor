<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the register page is not available', function () {
    $this->get('/register')
        ->assertNotFound();
});
