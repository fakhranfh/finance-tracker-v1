<?php

use App\Models\Record;
use App\Models\User;

it('rejects a guest from exporting records', function () {
    $this->getJson('/api/records/export/xlsx')->assertStatus(401);
});

it('exports records as xlsx', function () {
    Record::factory()->count(3)->create();

    $response = $this->actingAs(User::factory()->create())->get('/api/records/export/xlsx');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');
    expect(substr($response->streamedContent(), 0, 2))->toBe('PK');
});

it('exports records as pdf honouring filters', function () {
    Record::factory()->create(['type' => 'income']);
    Record::factory()->create(['type' => 'expense']);

    $response = $this->actingAs(User::factory()->create())->get('/api/records/export/pdf?type[]=income');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('rejects an unknown export format', function () {
    $this->actingAs(User::factory()->create())->get('/api/records/export/csv')->assertNotFound();
});

it('writes xlsx dates in the user timezone', function () {
    Record::factory()->create(['recorded_at' => '2026-10-01 20:00:00']);
    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

    $content = $this->actingAs($user)->get('/api/records/export/xlsx')->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'x');
    file_put_contents($path, $content);
    $zip = new ZipArchive;
    $zip->open($path);
    $sheet = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    unlink($path);

    expect($sheet)->toContain('02 Oct 2026, 03:00');
});
