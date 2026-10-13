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

it('creates a goal as active when no status is given', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', [
        'name' => 'Trip',
        'target_amount' => 1000,
        'saved_amount' => 0,
        'desired_date' => '2027-01-01',
        'color' => '#112233',
        'icon' => array_key_first(config('goal.icons')),
    ])->assertCreated()->assertJsonPath('data.status', 'active');
});

it('rejects creating a goal with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'target_amount', 'saved_amount', 'desired_date', 'color', 'icon']);
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

it('adds to the saved amount and records it in the history', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);

    $response = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 250.5]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.amount', '250.50');
    $response->assertJsonPath('goal.saved_amount', '1250.50');
    $this->assertDatabaseHas('goal_savings', ['goal_id' => $target->id, 'amount' => 250.5]);
    expect($target->fresh()->saved_amount)->toBe('1250.50');
});

it('rejects an invalid saving amount', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);

    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 0])
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", [])
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);

    expect($target->fresh()->saved_amount)->toBe('1000.00');
    $this->assertDatabaseCount('goal_savings', 0);
});

it('rejects adding to a goal that is already reached', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Reached]);

    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 100])->assertForbidden();

    expect($target->fresh()->saved_amount)->toBe('1000.00');
    $this->assertDatabaseCount('goal_savings', 0);
});

it('rejects a guest from adding or listing savings', function () {
    $target = Goal::factory()->create();

    $this->postJson("/api/goals/{$target->id}/savings", ['amount' => 100])->assertStatus(401);
    $this->getJson("/api/goals/{$target->id}/savings")->assertStatus(401);
});

it('lists the saving history of a goal, newest first', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);
    $other = Goal::factory()->create(['status' => GoalStatus::Active]);

    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 100])->assertStatus(201);
    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 200])->assertStatus(201);
    $this->actingAs($actor)->postJson("/api/goals/{$other->id}/savings", ['amount' => 300])->assertStatus(201);

    $response = $this->actingAs($actor)->getJson("/api/goals/{$target->id}/savings");

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'goal_id', 'amount', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.amount'))->toBe('200.00');
});

it('deletes the saving history together with the goal', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);

    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 100])->assertStatus(201);
    $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}")->assertNoContent();

    $this->assertDatabaseCount('goal_savings', 0);
});

it('generates sample savings for a goal in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 0, 'status' => GoalStatus::Active]);

    $response = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings/generate", ['count' => 4]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(4);
    expect($target->savings()->count())->toBe(4);
    expect((float) $target->fresh()->saved_amount)->toBe((float) $target->savings()->sum('amount'));
});

it('rejects generating sample savings outside local, for a reached goal, or with a bad count', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $active = Goal::factory()->create(['status' => GoalStatus::Active]);
    $reached = Goal::factory()->create(['status' => GoalStatus::Reached]);

    app()->detectEnvironment(fn () => 'production');
    $this->actingAs($actor)->postJson("/api/goals/{$active->id}/savings/generate", ['count' => 2])->assertNotFound();

    app()->detectEnvironment(fn () => 'local');
    $this->actingAs($actor)->postJson("/api/goals/{$reached->id}/savings/generate", ['count' => 2])->assertForbidden();
    $this->actingAs($actor)->postJson("/api/goals/{$active->id}/savings/generate", ['count' => 0])->assertStatus(422);
});

it('edits a saving and adjusts the saved amount by the difference', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);
    $saving = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 200])->json('data');

    $response = $this->actingAs($actor)->putJson("/api/goals/{$target->id}/savings/{$saving['id']}", ['amount' => 500]);

    $response->assertOk();
    $response->assertJsonPath('data.amount', '500.00');
    $response->assertJsonPath('goal.saved_amount', '1500.00');
    expect($target->fresh()->saved_amount)->toBe('1500.00');
});

it('deletes a saving and subtracts it from the saved amount', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);
    $saving = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 200])->json('data');

    $response = $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings/{$saving['id']}");

    $response->assertOk();
    $response->assertJsonPath('goal.saved_amount', '1000.00');
    $this->assertDatabaseMissing('goal_savings', ['id' => $saving['id']]);
});

it('rejects editing or deleting a saving with invalid input, on a reached goal, or of another goal', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);
    $other = Goal::factory()->create(['status' => GoalStatus::Active]);
    $saving = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 200])->json('data');

    $this->actingAs($actor)->putJson("/api/goals/{$target->id}/savings/{$saving['id']}", ['amount' => 0])
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
    $this->actingAs($actor)->putJson("/api/goals/{$other->id}/savings/{$saving['id']}", ['amount' => 5])->assertNotFound();
    $this->actingAs($actor)->deleteJson("/api/goals/{$other->id}/savings/{$saving['id']}")->assertNotFound();

    $target->update(['status' => GoalStatus::Reached]);

    $this->actingAs($actor)->putJson("/api/goals/{$target->id}/savings/{$saving['id']}", ['amount' => 5])->assertForbidden();
    $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings/{$saving['id']}")->assertForbidden();
    $this->assertDatabaseHas('goal_savings', ['id' => $saving['id'], 'amount' => 200]);
});

it('sorts the saving history by amount', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);

    foreach ([300, 100, 200] as $amount) {
        $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => $amount]);
    }

    $amounts = $this->actingAs($actor)->getJson("/api/goals/{$target->id}/savings?sort=amount&direction=asc")->json('data.*.amount');

    expect($amounts)->toBe(['100.00', '200.00', '300.00']);
});

it('bulk deletes savings by id and subtracts them from the saved amount', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 1000, 'status' => GoalStatus::Active]);
    $ids = collect([100, 200, 300])->map(
        fn ($amount) => $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => $amount])->json('data.id')
    );

    $response = $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings", ['ids' => $ids->take(2)->all()]);

    $response->assertOk();
    $response->assertJsonPath('goal.saved_amount', '1300.00');
    expect($target->savings()->count())->toBe(1);
});

it('bulk deletes every saving of a goal when all is set, leaving other goals untouched', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['saved_amount' => 0, 'status' => GoalStatus::Active]);
    $other = Goal::factory()->create(['saved_amount' => 0, 'status' => GoalStatus::Active]);
    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 100]);
    $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 200]);
    $this->actingAs($actor)->postJson("/api/goals/{$other->id}/savings", ['amount' => 50]);

    $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings", ['all' => true])
        ->assertOk()->assertJsonPath('goal.saved_amount', '0.00');

    expect($target->savings()->count())->toBe(0);
    expect($other->savings()->count())->toBe(1);
});

it('rejects bulk deleting savings without ids or on a reached goal', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);
    $id = $this->actingAs($actor)->postJson("/api/goals/{$target->id}/savings", ['amount' => 100])->json('data.id');

    $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings", [])->assertStatus(422);

    $target->update(['status' => GoalStatus::Reached]);

    $this->actingAs($actor)->deleteJson("/api/goals/{$target->id}/savings", ['ids' => [$id]])->assertForbidden();
    expect($target->savings()->count())->toBe(1);
});

it('filters the saving history by date range in the user timezone', function () {
    $actor = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $target = Goal::factory()->create(['status' => GoalStatus::Active]);

    $dates = ['2026-03-10 20:00:00' => 100, '2026-03-10 16:30:00' => 200, '2026-04-01 05:00:00' => 300];

    foreach ($dates as $at => $amount) {
        $saving = $target->savings()->create(['amount' => $amount]);
        $saving->forceFill(['created_at' => $at])->save();
    }

    // 2026-03-10 20:00 UTC is already 11 March in Jakarta (UTC+7).
    $amountsFor = fn (string $query) => $this->actingAs($actor)->getJson("/api/goals/{$target->id}/savings?sort=amount&direction=asc&{$query}")->json('data.*.amount');

    expect($amountsFor('date_from=2026-03-11&date_to=2026-03-31'))->toBe(['100.00']);
    expect($amountsFor('date_from=2026-03-10&date_to=2026-03-10'))->toBe(['200.00']);
    expect($amountsFor('date_from=2026-04-01'))->toBe(['300.00']);
    expect($amountsFor('date_to=2026-03-10'))->toBe(['200.00']);
    expect($amountsFor(''))->toHaveCount(3);
});

it('rejects an invalid saving history date range', function () {
    $actor = User::factory()->create();
    $target = Goal::factory()->create();

    $this->actingAs($actor)->getJson("/api/goals/{$target->id}/savings?date_from=nope")->assertStatus(422)->assertJsonValidationErrors(['date_from']);
    $this->actingAs($actor)->getJson("/api/goals/{$target->id}/savings?date_from=2026-05-02&date_to=2026-05-01")->assertStatus(422)->assertJsonValidationErrors(['date_to']);
});
