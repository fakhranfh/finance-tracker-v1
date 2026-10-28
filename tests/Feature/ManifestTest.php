<?php

it('serves a web manifest named after APP_NAME', function (): void {
    config(['app.name' => 'My Wallet']);

    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('name', 'My Wallet')
        ->assertJsonPath('short_name', 'My Wallet');
});
