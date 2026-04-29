<?php

test('the application returns a successful response', function () {
    $this->get('/')->assertRedirect(route('login'));
});
