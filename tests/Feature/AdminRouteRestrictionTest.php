<?php

use App\Models\User;

dataset('user feature pages', [
    '/accounts', '/records', '/categories', '/goals', '/webhooks', '/debts', '/receivables',
]);

dataset('user feature apis', [
    '/api/accounts', '/api/records', '/api/categories', '/api/goals', '/api/webhooks', '/api/debts',
]);

it('forbids an admin from the user feature pages', function (string $path) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get($path)->assertForbidden();
})->with('user feature pages');

it('forbids an admin from the user feature apis', function (string $path) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson($path)->assertForbidden();
})->with('user feature apis');

it('still lets an admin open the dashboard and user management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/dashboard')->assertOk();
    $this->actingAs($admin)->get('/users')->assertOk();
    $this->actingAs($admin)->getJson('/api/users')->assertOk();
});

it('still lets a regular user open the user feature pages and apis', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/accounts')->assertOk();
    $this->actingAs($user)->getJson('/api/accounts')->assertOk();
});
