<?php

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\SubGoal;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function goalPayload(array $overrides = []): array
{
    return [
        'name' => 'Dana Pernikahan',
        'target_amount' => 10000000,
        'saved_amount' => 3000000,
        'desired_date' => '2027-06-30',
        'color' => '#EC4899',
        'icon' => 'heart',
        'status' => 'active',
        'note' => null,
        ...$overrides,
    ];
}

function subGoalPayload(array $overrides = []): array
{
    return ['name' => 'Mas kawin', 'target_amount' => 4000000, 'saved_amount' => 1000000, 'desired_date' => '2027-01-15', 'note' => null, ...$overrides];
}

it('rejects a guest from listing sub goals', function () {
    $goal = Goal::factory()->create();

    $this->getJson("/api/goals/{$goal->id}/sub-goals")->assertStatus(401);
});

it('lists only the sub goals of the goal with a summary', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active]);
    SubGoal::factory()->for($goal)->create(['target_amount' => 1000, 'saved_amount' => 400, 'is_done' => true]);
    SubGoal::factory()->for($goal)->create(['target_amount' => 2500.5, 'saved_amount' => 100, 'is_done' => false]);
    SubGoal::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/goals/{$goal->id}/sub-goals");

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'goal_id', 'name', 'target_amount', 'saved_amount', 'desired_date', 'is_done', 'note', 'created_at']]])
        ->assertJsonPath('summary.total', 2)
        ->assertJsonPath('summary.done', 1)
        ->assertJsonPath('summary.target_total', '3500.50')
        ->assertJsonPath('summary.saved_total', '500.00');
    expect($response->json('data'))->toHaveCount(2);
});

it('searches and sorts sub goals via the api', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create();
    SubGoal::factory()->for($goal)->create(['name' => 'Findable entry', 'target_amount' => 200]);
    SubGoal::factory()->for($goal)->create(['name' => 'Other', 'target_amount' => 100]);

    expect($this->actingAs($actor)->getJson("/api/goals/{$goal->id}/sub-goals?search=Findable")->json('data'))->toHaveCount(1);

    $sorted = $this->actingAs($actor)->getJson("/api/goals/{$goal->id}/sub-goals?sort=target_amount&direction=asc");
    expect(collect($sorted->json('data'))->pluck('name')->all())->toBe(['Other', 'Findable entry']);
});

it('shows a sub goal only through its own goal', function () {
    $actor = User::factory()->create();
    $target = SubGoal::factory()->create();
    $other = Goal::factory()->create();

    $this->actingAs($actor)->getJson("/api/goals/{$target->goal_id}/sub-goals/{$target->id}")->assertOk()->assertJsonPath('data.id', $target->id);
    $this->actingAs($actor)->getJson("/api/goals/{$other->id}/sub-goals/{$target->id}")->assertNotFound();
});

it('updates the details of a sub goal but never its amounts', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'desired_date' => '2027-06-30']);
    $target = SubGoal::factory()->for($goal)->create(['is_done' => false, 'name' => 'Old', 'target_amount' => 100, 'saved_amount' => 40]);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", ['name' => 'New', 'desired_date' => '2027-01-01', 'note' => 'Hi', 'is_done' => true, 'target_amount' => 5, 'saved_amount' => 5])
        ->assertOk()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.is_done', true)
        ->assertJsonPath('data.target_amount', '100.00')
        ->assertJsonPath('data.saved_amount', '40.00');
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", ['is_done' => false])->assertOk()->assertJsonPath('data.name', 'New');
});

it('rejects an empty or invalid sub goal update', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'desired_date' => '2027-06-30']);
    $target = SubGoal::factory()->for($goal)->create();

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", [])->assertStatus(422);
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", ['name' => '', 'is_done' => true])->assertStatus(422)->assertJsonValidationErrors(['name']);
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", ['desired_date' => '2027-07-01', 'is_done' => true])->assertStatus(422)->assertJsonValidationErrors(['desired_date']);
});

it('does not let a reached goal change the done flag of its sub goals', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Reached]);
    $target = SubGoal::factory()->for($goal)->create();

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$target->id}", ['is_done' => true])->assertForbidden();
    $this->actingAs($actor)->getJson("/api/goals/{$goal->id}/sub-goals")->assertOk();
});

it('has no endpoints to create or delete a single sub goal', function () {
    $actor = User::factory()->create();
    $target = SubGoal::factory()->create();

    $this->actingAs($actor)->postJson("/api/goals/{$target->goal_id}/sub-goals", subGoalPayload())->assertStatus(405);
    $this->actingAs($actor)->deleteJson("/api/goals/{$target->goal_id}/sub-goals/{$target->id}")->assertStatus(405);
});

it('creates a goal together with sub goals that add up to it', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/goals', goalPayload([
        'sub_goals' => [
            subGoalPayload(),
            subGoalPayload(['name' => 'Vendor katering', 'target_amount' => 6000000, 'saved_amount' => 2000000, 'desired_date' => null]),
        ],
    ]));

    $response->assertStatus(201);
    $goal = Goal::query()->findOrFail($response->json('data.id'));
    expect($goal->subGoals()->count())->toBe(2)
        ->and((float) $goal->subGoals()->sum('target_amount'))->toBe(10000000.0)
        ->and((float) $goal->subGoals()->sum('saved_amount'))->toBe(3000000.0);
});

it('creates a goal without sub goals as before', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', goalPayload())->assertStatus(201);
    $this->actingAs($actor)->postJson('/api/goals', goalPayload(['sub_goals' => []]))->assertStatus(201);

    expect(SubGoal::query()->count())->toBe(0);
});

it('rejects sub goals whose targets do not add up to the goal target', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', goalPayload(['sub_goals' => [subGoalPayload(['target_amount' => 9000000, 'saved_amount' => 3000000])]]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sub_goals']);

    expect(Goal::query()->count())->toBe(0);
});

it('rejects sub goals whose saved amounts do not add up to the goal saved amount', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', goalPayload(['sub_goals' => [subGoalPayload(['target_amount' => 10000000, 'saved_amount' => 500000])]]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sub_goals']);
});

it('rejects invalid sub goal fields and a date later than the goal date', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals', goalPayload(['sub_goals' => [
        ['name' => '', 'target_amount' => 0, 'desired_date' => '2027-07-01'],
    ]]))->assertStatus(422)->assertJsonValidationErrors(['sub_goals.0.name', 'sub_goals.0.target_amount', 'sub_goals.0.desired_date']);

    $this->actingAs($actor)->postJson('/api/goals', goalPayload(['sub_goals' => [subGoalPayload(['id' => 1, 'target_amount' => 10000000, 'saved_amount' => 3000000])]]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sub_goals.0.id']);
});

it('syncs the sub goals of a goal on update and keeps the saved amount of existing ones', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 10000000, 'saved_amount' => 3000000, 'desired_date' => '2027-06-30']);
    $keep = SubGoal::factory()->for($goal)->create(['name' => 'Old name', 'target_amount' => 4000000, 'saved_amount' => 3000000]);
    $drop = SubGoal::factory()->for($goal)->create(['target_amount' => 6000000, 'saved_amount' => 0]);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['sub_goals' => [
        ['id' => $keep->id, 'name' => 'New name', 'target_amount' => 7000000, 'saved_amount' => 999],
        subGoalPayload(['name' => 'Fresh', 'target_amount' => 3000000, 'saved_amount' => 0]),
    ]]))->assertOk();

    expect($keep->fresh()->name)->toBe('New name')
        ->and($keep->fresh()->saved_amount)->toBe('3000000.00')
        ->and(SubGoal::query()->whereKey($drop->id)->exists())->toBeFalse()
        ->and($goal->subGoals()->count())->toBe(2);
});

it('rejects an update whose sub goals do not add up', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 10000000, 'saved_amount' => 0, 'desired_date' => '2027-06-30']);
    $existing = SubGoal::factory()->for($goal)->create(['target_amount' => 10000000, 'saved_amount' => 0]);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['saved_amount' => 0, 'sub_goals' => [
        ['id' => $existing->id, 'name' => 'X', 'target_amount' => 9000000],
    ]]))->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);

    // The saved amount of an existing sub goal comes from the database, not from the request.
    $existing->update(['saved_amount' => 500000]);
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['saved_amount' => 0, 'sub_goals' => [
        ['id' => $existing->id, 'name' => 'X', 'target_amount' => 10000000, 'saved_amount' => 0],
    ]]))->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);
});

it('leaves the sub goals alone when an update does not send them', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active]);
    SubGoal::factory()->count(2)->for($goal)->create();

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['target_amount' => $goal->target_amount, 'saved_amount' => $goal->saved_amount, 'desired_date' => $goal->desired_date]))->assertOk();

    expect($goal->subGoals()->count())->toBe(2);
});

it('does not remove a sub goal that has savings', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 10000000, 'saved_amount' => 0, 'desired_date' => '2027-06-30']);
    $keep = SubGoal::factory()->for($goal)->create(['target_amount' => 5000000]);
    $used = SubGoal::factory()->for($goal)->create(['target_amount' => 5000000]);
    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 1000, 'sub_goal_id' => $used->id])->assertStatus(201);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['saved_amount' => 1000, 'sub_goals' => [
        ['id' => $keep->id, 'name' => 'Keep', 'target_amount' => 10000000],
    ]]))->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);
});

it('removes every sub goal when an update sends an empty list', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 1000, 'saved_amount' => 0, 'desired_date' => '2027-06-30']);
    SubGoal::factory()->for($goal)->create(['target_amount' => 1000]);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}", goalPayload(['target_amount' => 1000, 'saved_amount' => 0, 'sub_goals' => []]))->assertOk();

    expect($goal->subGoals()->count())->toBe(0);
});

it('deletes the sub goals together with their goal', function () {
    $goal = Goal::factory()->create();
    SubGoal::factory()->count(2)->for($goal)->create();

    $goal->delete();

    expect(SubGoal::query()->count())->toBe(0);
});

it('generates sample goals whose sub goals add up to the goal', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/goals/generate', ['count' => 6])->assertOk();

    Goal::query()->get()->each(function (Goal $goal): void {
        expect($goal->subGoals()->count())->toBeGreaterThanOrEqual(2)
            ->and(round((float) $goal->subGoals()->sum('target_amount'), 2))->toBe(round((float) $goal->target_amount, 2))
            ->and(round((float) $goal->subGoals()->sum('saved_amount'), 2))->toBe(round((float) $goal->saved_amount, 2))
            ->and($goal->subGoals()->where('desired_date', '>', $goal->desired_date)->count())->toBe(0);
    });
});

it('records a saving for a sub goal, which also raises the sub goal saved amount', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'saved_amount' => 0]);
    $subGoal = SubGoal::factory()->for($goal)->create(['name' => 'Vendor katering', 'saved_amount' => 0]);

    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100, 'sub_goal_id' => $subGoal->id])
        ->assertStatus(201)
        ->assertJsonPath('data.sub_goal_id', $subGoal->id)
        ->assertJsonPath('data.sub_goal_name', 'Vendor katering');

    expect($subGoal->fresh()->saved_amount)->toBe('100.00')
        ->and($goal->fresh()->saved_amount)->toBe('100.00');
});

it('requires a sub goal for a saving once the goal has sub goals, and rejects one of another goal', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active]);

    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100])->assertStatus(201);

    SubGoal::factory()->for($goal)->create();
    $foreign = SubGoal::factory()->create();

    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_goal_id']);
    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100, 'sub_goal_id' => $foreign->id])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_goal_id']);
});

it('moves the sub goal saved amount when a saving is edited, deleted or bulk deleted', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'saved_amount' => 0]);
    $first = SubGoal::factory()->for($goal)->create(['saved_amount' => 0]);
    $second = SubGoal::factory()->for($goal)->create(['saved_amount' => 0]);
    $saving = $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 200, 'sub_goal_id' => $first->id])->json('data');
    $other = $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 50, 'sub_goal_id' => $first->id])->json('data');

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/savings/{$saving['id']}", ['amount' => 300, 'sub_goal_id' => $second->id])->assertOk();
    expect($first->fresh()->saved_amount)->toBe('50.00')
        ->and($second->fresh()->saved_amount)->toBe('300.00')
        ->and($goal->fresh()->saved_amount)->toBe('350.00');

    $this->actingAs($actor)->deleteJson("/api/goals/{$goal->id}/savings/{$saving['id']}")->assertOk();
    expect($second->fresh()->saved_amount)->toBe('0.00');

    $this->actingAs($actor)->deleteJson("/api/goals/{$goal->id}/savings", ['ids' => [$other['id']]])->assertOk();
    expect($first->fresh()->saved_amount)->toBe('0.00')
        ->and($goal->fresh()->saved_amount)->toBe('0.00');
});

it('replaces the sub goals of a goal on their own', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 10000000, 'saved_amount' => 3000000, 'desired_date' => '2027-06-30']);
    $keep = SubGoal::factory()->for($goal)->create(['name' => 'Old', 'target_amount' => 4000000, 'saved_amount' => 3000000]);
    SubGoal::factory()->for($goal)->create(['target_amount' => 6000000, 'saved_amount' => 0]);

    $response = $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals", ['sub_goals' => [
        ['id' => $keep->id, 'name' => 'Renamed', 'target_amount' => 7000000],
        ['name' => 'Fresh', 'target_amount' => 3000000, 'saved_amount' => 0, 'desired_date' => '2027-06-30'],
    ]]);

    $response->assertOk()->assertJsonPath('summary.total', 2)->assertJsonPath('summary.target_total', '10000000.00');
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Renamed', 'Fresh'])
        ->and($keep->fresh()->saved_amount)->toBe('3000000.00');
});

it('rejects replacing the sub goals with a list that does not add up or is too late', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'target_amount' => 10000000, 'saved_amount' => 0, 'desired_date' => '2027-06-30']);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals", ['sub_goals' => [['name' => 'Part', 'target_amount' => 9000000]]])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals", ['sub_goals' => [['name' => 'Late', 'target_amount' => 10000000, 'desired_date' => '2027-07-01']]])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_goals.0.desired_date']);
    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals", [])->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);
});

it('can empty the sub goals unless one has savings, and not on a reached goal', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'saved_amount' => 0]);
    $subGoal = SubGoal::factory()->for($goal)->create(['saved_amount' => 0]);
    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100, 'sub_goal_id' => $subGoal->id])->assertStatus(201);

    $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals", ['sub_goals' => []])->assertStatus(422)->assertJsonValidationErrors(['sub_goals']);

    $empty = Goal::factory()->create(['status' => GoalStatus::Active]);
    SubGoal::factory()->for($empty)->create(['saved_amount' => 0]);
    $this->actingAs($actor)->putJson("/api/goals/{$empty->id}/sub-goals", ['sub_goals' => []])->assertOk();
    expect($empty->subGoals()->count())->toBe(0);

    $reached = Goal::factory()->create(['status' => GoalStatus::Reached]);
    $this->actingAs($actor)->putJson("/api/goals/{$reached->id}/sub-goals", ['sub_goals' => []])->assertForbidden();
});

it('lists only the savings of one sub goal when filtered', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active, 'saved_amount' => 0]);
    $first = SubGoal::factory()->for($goal)->create(['saved_amount' => 0]);
    $second = SubGoal::factory()->for($goal)->create(['saved_amount' => 0]);
    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 100, 'sub_goal_id' => $first->id])->assertStatus(201);
    $this->actingAs($actor)->postJson("/api/goals/{$goal->id}/savings", ['amount' => 200, 'sub_goal_id' => $second->id])->assertStatus(201);

    $response = $this->actingAs($actor)->getJson("/api/goals/{$goal->id}/savings?sub_goal_id={$second->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.amount'))->toBe('200.00');
    expect($this->actingAs($actor)->getJson("/api/goals/{$goal->id}/savings")->json('data'))->toHaveCount(2);
});

it('logs a done entry in the saving history when a sub goal is marked done and removes it when unmarked', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active]);
    $target = SubGoal::factory()->for($goal)->create(['is_done' => false]);
    $url = "/api/goals/{$goal->id}/sub-goals/{$target->id}";

    $this->actingAs($actor)->putJson($url, ['is_done' => true])->assertOk();
    $this->actingAs($actor)->putJson($url, ['is_done' => true])->assertOk();

    expect($goal->savings()->where('sub_goal_id', $target->id)->where('type', 'done')->count())->toBe(1);

    $this->actingAs($actor)->putJson($url, ['is_done' => false])->assertOk();

    expect($goal->savings()->count())->toBe(0);
});

it('marks the sub goal as not done when its done history entry is deleted', function () {
    $actor = User::factory()->create();
    $goal = Goal::factory()->create(['status' => GoalStatus::Active]);
    $one = SubGoal::factory()->for($goal)->create(['is_done' => false]);
    $two = SubGoal::factory()->for($goal)->create(['is_done' => false]);

    foreach ([$one, $two] as $subGoal) {
        $this->actingAs($actor)->putJson("/api/goals/{$goal->id}/sub-goals/{$subGoal->id}", ['is_done' => true])->assertOk();
    }

    $entry = $goal->savings()->where('sub_goal_id', $one->id)->firstOrFail();
    $this->actingAs($actor)->deleteJson("/api/goals/{$goal->id}/savings/{$entry->id}")->assertOk();

    expect($one->fresh()->is_done)->toBeFalse()->and($two->fresh()->is_done)->toBeTrue();

    $this->actingAs($actor)->deleteJson("/api/goals/{$goal->id}/savings", ['all' => true])->assertOk();

    expect($two->fresh()->is_done)->toBeFalse();
});
