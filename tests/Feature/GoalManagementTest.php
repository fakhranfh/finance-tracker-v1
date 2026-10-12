<?php

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing goals', function () {
    $this->getJson('/api/goals')->assertStatus(401);
});

it('lists goals via the api', function () {
    $actor = User::factory()->create();
    Goal::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/goals');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'target_amount', 'saved_amount', 'desired_date', 'color', 'icon', 'status', 'note', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches goals via the api', function () {
    $actor = User::factory()->create();
    Goal::factory()->create(['name' => 'Findable entry']);
    Goal::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/goals?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a goal via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals', [
        'name' => 'Sample name',
        'target_amount' => 9.99,
        'saved_amount' => 9.99,
        'desired_date' => '2026-01-15',
        'color' => '#2563EB',
        'icon' => 'flag',
        'status' => 'paused',
        'note' => 'Sample note',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $response->assertJsonPath('data.status', 'paused');
    $this->assertDatabaseHas('goals', ['name' => 'Sample name']);
});

it('creates a goal without a note', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', [
        'name' => 'No note',
        'target_amount' => 1000000,
        'saved_amount' => 0,
        'desired_date' => '2026-12-31',
        'color' => '#2563EB',
        'icon' => 'flag',
        'status' => 'active',
    ])->assertStatus(201)->assertJsonPath('data.note', null);
});

it('rejects invalid goal amounts, color and icon', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals', [
        'name' => 'Bad',
        'target_amount' => 0,
        'saved_amount' => -5,
        'desired_date' => 'not-a-date',
        'color' => 'blue',
        'icon' => 'unknown',
        'status' => 'done',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['target_amount', 'saved_amount', 'desired_date', 'color', 'icon', 'status']);
});

it('returns the desired date as a plain calendar date', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['desired_date' => '2026-12-31']);

    $this->actingAs($actor)->getJson("/api/goals/{$goal->id}")->assertJsonPath('data.desired_date', '2026-12-31');
});

it('rejects creating a goal with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'target_amount', 'saved_amount', 'desired_date', 'color', 'icon', 'status']);
});

it('shows a goal via the api', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/goals/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a goal via the api', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);

    $response = $this->actingAs($actor)->putJson("/api/goals/{$target->id}", [
        'name' => 'Updated name',
        'target_amount' => 19.5,
        'saved_amount' => 19.5,
        'desired_date' => '2026-02-20',
        'color' => '#10B981',
        'icon' => 'home',
        'status' => 'reached',
        'note' => 'Updated note',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated name');
    $response->assertJsonPath('data.status', 'reached');
});

it('rejects updating a goal that is already reached', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['name' => 'Locked goal', 'status' => GoalStatus::Reached]);

    $this->actingAs($actor)->putJson("/api/goals/{$target->id}", [
        'name' => 'Changed name',
        'target_amount' => 100,
        'saved_amount' => 100,
        'desired_date' => '2026-02-20',
        'color' => '#10B981',
        'icon' => 'home',
        'status' => 'active',
        'note' => null,
    ])->assertForbidden();

    expect($target->fresh()->name)->toBe('Locked goal');
    expect($target->fresh()->status)->toBe(GoalStatus::Reached);
});

it('deletes a goal via the api', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('goals', ['id' => $target->id]);
});

it('bulk deletes goals by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Goal::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/goals', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Goal::query()->count())->toBe(1);
});

it('bulk deletes every goal when all is set', function () {
    $actor = User::factory()->create();
    Goal::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/goals', ['all' => true])->assertNoContent();

    expect(Goal::query()->count())->toBe(0);
});

it('generates sample goals via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Goal::query()->count())->toBe(5);
});

it('rejects generating sample goals outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals/generate', ['count' => 5])->assertNotFound();
});
