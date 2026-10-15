<?php

use Illuminate\Support\Facades\Config;

it('allows a configured origin', function () {
    Config::set('cors.allowed_origins', ['https://app.example.com', 'https://admin.example.com']);

    $this->withHeaders(['Origin' => 'https://app.example.com'])
        ->getJson('/api/v1/ping')
        ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
});

it('does not allow an origin outside the list', function () {
    Config::set('cors.allowed_origins', ['https://app.example.com', 'https://admin.example.com']);

    $this->withHeaders(['Origin' => 'https://evil.example.com'])
        ->getJson('/api/v1/ping')
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

it('answers preflight only for allowed origins', function () {
    Config::set('cors.allowed_origins', ['https://app.example.com', 'https://admin.example.com']);

    $headers = ['Access-Control-Request-Method' => 'POST'];

    $this->withHeaders($headers + ['Origin' => 'https://app.example.com'])
        ->options('/api/v1/ping')
        ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');

    $this->withHeaders($headers + ['Origin' => 'https://evil.example.com'])
        ->options('/api/v1/ping')
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});
