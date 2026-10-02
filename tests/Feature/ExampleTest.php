<?php

use App\Models\User;

test('home redirects to register when there are no users', function () {
    $this->get('/')->assertRedirect(route('register'));
});

test('home redirects to login when a user exists', function () {
    User::factory()->create();

    $this->get('/')->assertRedirect(route('login'));
});
