<?php

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\CycleEntry;
use App\Models\CycleType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * A type whose purchases lasted 10 and 20 days (average 15), all priced 30.000, plus one still in use.
 * Monthly cost = 30.000 / 15 * 30 = 60.000.
 */
function cycleTypeWithHistory(?Category $category = null): CycleType
{
    $type = CycleType::factory()->create($category ? ['category_id' => $category->id] : []);

    CycleEntry::factory()->for($type)->create(['name' => 'Nivea Body Lotion', 'price' => 30000, 'bought_at' => '2026-08-01', 'finished_at' => '2026-08-11']);
    CycleEntry::factory()->for($type)->create(['name' => 'Nivea Body Lotion', 'price' => 30000, 'bought_at' => '2026-08-20', 'finished_at' => '2026-09-09']);
    CycleEntry::factory()->for($type)->create(['name' => 'Nivea Body Lotion', 'price' => 30000, 'bought_at' => '2026-09-15']);

    return $type;
}

it('rejects a guest from the usage cycle endpoints', function () {
    $this->getJson('/api/cycle-categories')->assertUnauthorized();
    $this->getJson('/api/cycle-types/1')->assertUnauthorized();
    $this->getJson('/api/cycle-entries/1')->assertUnauthorized();
});

it('lists only expense categories with their monthly cost and the total', function () {
    $actor = User::factory()->create();
    $food = Category::factory()->create(['name' => 'Body Care']);
    $skin = Category::factory()->create(['name' => 'Skin Care']);
    Category::factory()->income()->create(['name' => 'Gaji']);
    cycleTypeWithHistory($food);
    cycleTypeWithHistory($food);
    cycleTypeWithHistory($skin);

    $response = $this->actingAs($actor)->getJson('/api/cycle-categories');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'color', 'icon', 'monthly']], 'summary' => ['monthly_total']])
        ->assertJsonPath('summary.monthly_total', 180000);
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Body Care', 'Skin Care'])
        ->and(collect($response->json('data'))->pluck('monthly')->all())->toBe([120000, 60000]);
});

it('pages and searches the expense categories while the monthly total covers all of them', function () {
    $actor = User::factory()->create();
    foreach (['Body Care', 'Skin Care', 'Hair Care'] as $name) {
        cycleTypeWithHistory(Category::factory()->create(['name' => $name]));
    }

    $first = $this->actingAs($actor)->getJson('/api/cycle-categories?per_page=2');

    $first->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('summary.monthly_total', 180000);
    expect(collect($first->json('data'))->pluck('name')->all())->toBe(['Body Care', 'Hair Care']);

    $second = $this->actingAs($actor)->getJson('/api/cycle-categories?per_page=2&page=2');
    expect(collect($second->json('data'))->pluck('name')->all())->toBe(['Skin Care'])
        ->and($second->json('data.0.monthly'))->toBe(60000);

    $search = $this->actingAs($actor)->getJson('/api/cycle-categories?search=skin');
    expect(collect($search->json('data'))->pluck('name')->all())->toBe(['Skin Care'])
        ->and($search->json('meta.total'))->toBe(1)
        ->and($search->json('summary.monthly_total'))->toBe(180000);
});

it('shows a category with zero monthly cost until a purchase has finished', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create();
    CycleEntry::factory()->for(CycleType::factory()->create(['category_id' => $category->id]))->create();

    $this->actingAs($actor)->getJson('/api/cycle-categories')
        ->assertOk()
        ->assertJsonPath('data.0.monthly', 0)
        ->assertJsonPath('summary.monthly_total', 0);
});

it('shows a category with its monthly cost', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Body Care']);
    cycleTypeWithHistory($category);

    $this->actingAs($actor)->getJson("/api/cycle-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', 'Body Care')
        ->assertJsonPath('data.monthly', 60000)
        ->assertJsonMissingPath('data.types');
});

it('lists the item types of a category with their numbers, paged and searchable', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Body Care']);
    $history = cycleTypeWithHistory($category);
    $history->update(['name' => 'Bravo']);
    CycleType::factory()->create(['category_id' => $category->id, 'name' => 'Alpha']);
    CycleType::factory()->create(['category_id' => $category->id, 'name' => 'Charlie']);
    cycleTypeWithHistory();

    $all = $this->actingAs($actor)->getJson("/api/cycle-categories/{$category->id}/types");

    $all->assertOk()
        ->assertJsonStructure(['data' => [['id', 'category_id', 'name', 'icon', 'avg_days', 'monthly']], 'meta' => ['total', 'last_page'], 'category' => ['id', 'name', 'monthly']])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('category.name', 'Body Care')
        ->assertJsonPath('category.monthly', 60000);
    expect(collect($all->json('data'))->pluck('name')->all())->toBe(['Alpha', 'Bravo', 'Charlie'])
        ->and($all->json('data.1.avg_days'))->toBe(15)
        ->and($all->json('data.1.monthly'))->toBe(60000)
        ->and($all->json('data.0.monthly'))->toBeNull()
        ->and($all->json('data.1'))->not->toHaveKey('entries');

    $paged = $this->actingAs($actor)->getJson("/api/cycle-categories/{$category->id}/types?per_page=2&page=2");
    expect(collect($paged->json('data'))->pluck('name')->all())->toBe(['Charlie'])
        ->and($paged->json('meta.last_page'))->toBe(2);

    $search = $this->actingAs($actor)->getJson("/api/cycle-categories/{$category->id}/types?search=brav");
    expect(collect($search->json('data'))->pluck('name')->all())->toBe(['Bravo'])
        ->and($search->json('category.monthly'))->toBe(60000);
});

it('does not list the item types of an income category', function () {
    $actor = User::factory()->create();
    $income = Category::factory()->income()->create();

    $this->actingAs($actor)->getJson("/api/cycle-categories/{$income->id}/types")->assertNotFound();
});

it('does not show an income category as a usage cycle category', function () {
    $actor = User::factory()->create();
    $income = Category::factory()->income()->create();

    $this->actingAs($actor)->getJson("/api/cycle-categories/{$income->id}")->assertNotFound();
});

it('adds an item type to a category', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($actor)->postJson("/api/cycle-categories/{$category->id}/types", ['name' => 'Sabun Mandi', 'icon' => 'sparkles'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sabun Mandi')
        ->assertJsonPath('data.category_id', $category->id)
        ->assertJsonPath('data.monthly', null);

    expect(CycleType::query()->where('name', 'Sabun Mandi')->value('user_id'))->toBe($actor->id);
});

it('defaults the icon of a new item type', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($actor)->postJson("/api/cycle-categories/{$category->id}/types", ['name' => 'Toner'])
        ->assertCreated()
        ->assertJsonPath('data.icon', 'tag');
});

it('validates a new item type', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $category = Category::factory()->create();
    CycleType::factory()->create(['category_id' => $category->id, 'name' => 'Serum']);
    $other = Category::factory()->create();

    $url = "/api/cycle-categories/{$category->id}/types";

    $this->actingAs($actor)->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->postJson($url, ['name' => str_repeat('a', 41)])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->postJson($url, ['name' => 'Serum'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->postJson($url, ['name' => 'Toner', 'icon' => 'nope'])->assertUnprocessable()->assertJsonValidationErrors('icon');
    $this->actingAs($actor)->postJson("/api/cycle-categories/{$other->id}/types", ['name' => 'Serum'])->assertCreated();
});

it('does not add an item type to an income category', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $income = Category::factory()->create(['type' => CategoryType::Income]);

    $this->actingAs($actor)->postJson("/api/cycle-categories/{$income->id}/types", ['name' => 'Bonus'])->assertNotFound();
});

it('shows an item type with its category and numbers', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Body Care']);
    $type = cycleTypeWithHistory($category);

    $this->actingAs($actor)->getJson("/api/cycle-types/{$type->id}")
        ->assertOk()
        ->assertJsonPath('data.category.name', 'Body Care')
        ->assertJsonPath('data.avg_days', 15)
        ->assertJsonPath('data.monthly', 60000)
        ->assertJsonMissingPath('data.entries');
});

it('lists the purchases of an item type oldest first, paged and searchable', function () {
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();
    $type->entries()->orderBy('bought_at')->first()->update(['name' => 'Vaseline']);

    $all = $this->actingAs($actor)->getJson("/api/cycle-types/{$type->id}/entries");

    $all->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'cycle_type_id', 'name', 'price', 'bought_at', 'finished_at']],
            'meta' => ['total', 'last_page'],
            'type' => ['id', 'name', 'category_id', 'avg_days', 'monthly', 'last_entry' => ['name', 'price']],
        ])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('type.avg_days', 15)
        ->assertJsonPath('type.monthly', 60000)
        ->assertJsonPath('type.last_entry.price', 30000);
    expect(collect($all->json('data'))->pluck('bought_at')->all())->toBe(['2026-08-01', '2026-08-20', '2026-09-15'])
        ->and($all->json('data.2.finished_at'))->toBeNull();

    $paged = $this->actingAs($actor)->getJson("/api/cycle-types/{$type->id}/entries?per_page=2&page=2");
    expect($paged->json('data'))->toHaveCount(1)
        ->and($paged->json('data.0.bought_at'))->toBe('2026-09-15')
        ->and($paged->json('type.avg_days'))->toBe(15);

    $search = $this->actingAs($actor)->getJson("/api/cycle-types/{$type->id}/entries?search=VASEL");
    expect(collect($search->json('data'))->pluck('name')->all())->toBe(['Vaseline'])
        ->and($search->json('type.monthly'))->toBe(60000);
});

it('has no last purchase for an item type without purchases', function () {
    $actor = User::factory()->create();
    $type = CycleType::factory()->create();

    $this->actingAs($actor)->getJson("/api/cycle-types/{$type->id}/entries")
        ->assertOk()
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('type.last_entry', null)
        ->assertJsonPath('type.avg_days', null);
});

it('adds a purchase to an item type', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = CycleType::factory()->create();

    $this->actingAs($actor)->postJson("/api/cycle-types/{$type->id}/entries", ['name' => 'Shinzui', 'price' => 35000, 'bought_at' => '2026-10-08'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Shinzui')
        ->assertJsonPath('data.price', 35000)
        ->assertJsonPath('data.bought_at', '2026-10-08')
        ->assertJsonPath('data.finished_at', null)
        ->assertJsonPath('data.type.id', $type->id);

    $this->assertDatabaseHas('cycle_entries', ['cycle_type_id' => $type->id, 'name' => 'Shinzui', 'price' => 35000]);
});

it('validates a new purchase', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = CycleType::factory()->create();
    $url = "/api/cycle-types/{$type->id}/entries";

    $this->actingAs($actor)->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'price', 'bought_at']);
    $this->actingAs($actor)->postJson($url, ['name' => 'X', 'price' => 0, 'bought_at' => '2026-10-08'])->assertUnprocessable()->assertJsonValidationErrors('price');
    $this->actingAs($actor)->postJson($url, ['name' => 'X', 'price' => 'abc', 'bought_at' => '2026-10-08'])->assertUnprocessable()->assertJsonValidationErrors('price');
    $this->actingAs($actor)->postJson($url, ['name' => 'X', 'price' => 1000, 'bought_at' => '08/10/2026'])->assertUnprocessable()->assertJsonValidationErrors('bought_at');
    $this->actingAs($actor)->postJson($url, ['name' => str_repeat('a', 81), 'price' => 1000, 'bought_at' => '2026-10-08'])->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('shows a purchase with the average days of its type', function () {
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();
    $entry = $type->entries()->orderBy('bought_at')->first();

    $this->actingAs($actor)->getJson("/api/cycle-entries/{$entry->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $entry->id)
        ->assertJsonPath('data.type.name', $type->name)
        ->assertJsonPath('data.type.category_id', $type->category_id)
        ->assertJsonPath('data.type.avg_days', 15);
});

it('updates a purchase', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $entry = CycleEntry::factory()->create(['name' => 'Old', 'price' => 1000, 'bought_at' => '2026-10-01']);

    $this->actingAs($actor)->putJson("/api/cycle-entries/{$entry->id}", ['name' => 'Shinzui', 'price' => 36000, 'bought_at' => '2026-10-02'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Shinzui')
        ->assertJsonPath('data.price', 36000)
        ->assertJsonPath('data.bought_at', '2026-10-02');
});

it('does not move the bought date after the date it ran out', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $entry = CycleEntry::factory()->create(['bought_at' => '2026-10-01', 'finished_at' => '2026-10-11']);

    $this->actingAs($actor)->putJson("/api/cycle-entries/{$entry->id}", ['name' => 'X', 'price' => 1000, 'bought_at' => '2026-10-20'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bought_at');
});

it('marks a purchase as finished', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $entry = CycleEntry::factory()->create(['bought_at' => '2026-10-01']);

    $this->actingAs($actor)->putJson("/api/cycle-entries/{$entry->id}/finish", ['finished_at' => '2026-10-29'])
        ->assertOk()
        ->assertJsonPath('data.finished_at', '2026-10-29')
        ->assertJsonPath('data.type.avg_days', 28);
});

it('validates the finish date', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $entry = CycleEntry::factory()->create(['bought_at' => '2026-10-10']);
    $url = "/api/cycle-entries/{$entry->id}/finish";

    $this->actingAs($actor)->putJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('finished_at');
    $this->actingAs($actor)->putJson($url, ['finished_at' => '2026-10-09'])->assertUnprocessable()->assertJsonValidationErrors('finished_at');
    $this->actingAs($actor)->putJson($url, ['finished_at' => '10/10/2026'])->assertUnprocessable()->assertJsonValidationErrors('finished_at');
    $this->actingAs($actor)->putJson($url, ['finished_at' => '2026-10-10'])->assertOk();
});

it('deletes a purchase', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $entry = CycleEntry::factory()->create();

    $this->actingAs($actor)->deleteJson("/api/cycle-entries/{$entry->id}")->assertNoContent();

    $this->assertDatabaseMissing('cycle_entries', ['id' => $entry->id]);
});

it('deletes several purchases of an item type at once', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();
    [$first, $second, $third] = $type->entries()->orderBy('bought_at')->pluck('id')->all();

    $this->actingAs($actor)->deleteJson("/api/cycle-types/{$type->id}/entries", ['ids' => [$first, $third]])->assertNoContent();

    $this->assertDatabaseMissing('cycle_entries', ['id' => $first]);
    $this->assertDatabaseMissing('cycle_entries', ['id' => $third]);
    $this->assertDatabaseHas('cycle_entries', ['id' => $second]);
});

it('validates a bulk delete of purchases', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();
    $elsewhere = CycleEntry::factory()->create();
    $url = "/api/cycle-types/{$type->id}/entries";

    $this->actingAs($actor)->deleteJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('ids');
    $this->actingAs($actor)->deleteJson($url, ['ids' => []])->assertUnprocessable()->assertJsonValidationErrors('ids');
    $this->actingAs($actor)->deleteJson($url, ['ids' => [$elsewhere->id]])->assertUnprocessable()->assertJsonValidationErrors('ids.0');
    $this->assertDatabaseHas('cycle_entries', ['id' => $elsewhere->id]);
});

it('renames an item name and changes its icon', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();

    $this->actingAs($actor)->putJson("/api/cycle-types/{$type->id}", ['name' => 'Sabun Muka', 'icon' => 'sun'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Sabun Muka')
        ->assertJsonPath('data.icon', 'sun')
        ->assertJsonPath('data.monthly', 60000);

    $this->assertDatabaseHas('cycle_types', ['id' => $type->id, 'name' => 'Sabun Muka', 'icon' => 'sun']);
    $this->actingAs($actor)->putJson("/api/cycle-types/{$type->id}", ['name' => 'Sabun Muka'])->assertOk();
});

it('validates an item name update', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = CycleType::factory()->create(['name' => 'Serum']);
    $sibling = CycleType::factory()->create(['category_id' => $type->category_id, 'name' => 'Toner']);
    $url = "/api/cycle-types/{$sibling->id}";

    $this->actingAs($actor)->putJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->putJson($url, ['name' => str_repeat('a', 41)])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->putJson($url, ['name' => 'Serum'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($actor)->putJson($url, ['name' => 'Toner', 'icon' => 'nope'])->assertUnprocessable()->assertJsonValidationErrors('icon');
    $this->actingAs($actor)->putJson($url, ['name' => 'Toner'])->assertOk();
});

it('deletes an item name with its purchases', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();

    $this->actingAs($actor)->deleteJson("/api/cycle-types/{$type->id}")->assertNoContent();

    $this->assertDatabaseMissing('cycle_types', ['id' => $type->id]);
    $this->assertDatabaseMissing('cycle_entries', ['cycle_type_id' => $type->id]);
});

it('deletes several item names of a category at once', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $category = Category::factory()->create();
    $first = cycleTypeWithHistory($category);
    $second = cycleTypeWithHistory($category);
    $third = cycleTypeWithHistory($category);

    $this->actingAs($actor)->deleteJson("/api/cycle-categories/{$category->id}/types", ['ids' => [$first->id, $third->id]])->assertNoContent();

    $this->assertDatabaseMissing('cycle_types', ['id' => $first->id]);
    $this->assertDatabaseMissing('cycle_types', ['id' => $third->id]);
    $this->assertDatabaseMissing('cycle_entries', ['cycle_type_id' => $first->id]);
    $this->assertDatabaseHas('cycle_types', ['id' => $second->id]);
    $this->assertDatabaseHas('cycle_entries', ['cycle_type_id' => $second->id]);
});

it('validates a bulk delete of item names', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();
    $elsewhere = CycleType::factory()->create();
    $url = "/api/cycle-categories/{$type->category_id}/types";

    $this->actingAs($actor)->deleteJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('ids');
    $this->actingAs($actor)->deleteJson($url, ['ids' => []])->assertUnprocessable()->assertJsonValidationErrors('ids');
    $this->actingAs($actor)->deleteJson($url, ['ids' => [$elsewhere->id]])->assertUnprocessable()->assertJsonValidationErrors('ids.0');
    $this->assertDatabaseHas('cycle_types', ['id' => $elsewhere->id]);
});

it('removes the item types and purchases of a deleted category', function () {
    $actor = User::factory()->create();
    $type = cycleTypeWithHistory();

    $type->category->delete();

    $this->assertDatabaseMissing('cycle_types', ['id' => $type->id]);
    $this->assertDatabaseMissing('cycle_entries', ['cycle_type_id' => $type->id]);
});

it('keeps the usage cycle data of each user apart', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $bob = User::factory()->create();
    $alice = User::factory()->create();

    $this->actingAs($bob);
    $bobCategory = Category::factory()->create(['name' => 'Bob Care']);
    $bobType = cycleTypeWithHistory($bobCategory);
    $bobEntry = $bobType->entries()->first();

    $this->actingAs($alice);
    Category::factory()->create(['name' => 'Alice Care']);

    $names = collect($this->getJson('/api/cycle-categories')->assertOk()->json('data'))->pluck('name')->all();
    expect($names)->toBe(['Alice Care'])
        ->and($this->getJson('/api/cycle-categories')->json('summary.monthly_total'))->toBe(0);

    $this->getJson("/api/cycle-categories/{$bobCategory->id}")->assertNotFound();
    $this->postJson("/api/cycle-categories/{$bobCategory->id}/types", ['name' => 'Hijack'])->assertNotFound();
    $this->getJson("/api/cycle-types/{$bobType->id}")->assertNotFound();
    $this->getJson("/api/cycle-types/{$bobType->id}/entries")->assertNotFound();
    $this->getJson("/api/cycle-categories/{$bobCategory->id}/types")->assertNotFound();
    $this->putJson("/api/cycle-types/{$bobType->id}", ['name' => 'Hijack'])->assertNotFound();
    $this->deleteJson("/api/cycle-types/{$bobType->id}")->assertNotFound();
    $this->deleteJson("/api/cycle-categories/{$bobCategory->id}/types", ['ids' => [$bobType->id]])->assertNotFound();
    $this->postJson("/api/cycle-types/{$bobType->id}/entries", ['name' => 'X', 'price' => 1000, 'bought_at' => '2026-10-08'])->assertNotFound();
    $this->getJson("/api/cycle-entries/{$bobEntry->id}")->assertNotFound();
    $this->putJson("/api/cycle-entries/{$bobEntry->id}", ['name' => 'X', 'price' => 1000, 'bought_at' => '2026-10-08'])->assertNotFound();
    $this->putJson("/api/cycle-entries/{$bobEntry->id}/finish", ['finished_at' => '2026-12-01'])->assertNotFound();
    $this->deleteJson("/api/cycle-entries/{$bobEntry->id}")->assertNotFound();
    $this->deleteJson("/api/cycle-types/{$bobType->id}/entries", ['ids' => [$bobEntry->id]])->assertNotFound();
});

it('generates sample purchases for an item name in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $actor = User::factory()->create();
    $type = CycleType::factory()->create(['name' => 'Sabun Mandi']);

    $response = $this->actingAs($actor)->postJson("/api/cycle-types/{$type->id}/entries/generate", ['count' => 6]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(6);

    $entries = $type->entries()->orderBy('bought_at')->get();

    expect($entries)->toHaveCount(6)
        ->and($entries->whereNull('finished_at'))->toHaveCount(1)
        ->and($entries->last()->finished_at)->toBeNull()
        ->and($entries->pluck('name')->unique())->toHaveCount(1)
        ->and($entries->every(fn (CycleEntry $entry) => $entry->price % 1000 === 0 && $entry->price >= 35000))->toBeTrue()
        ->and($entries->slice(0, 5)->every(fn (CycleEntry $entry) => $entry->finished_at >= $entry->bought_at))->toBeTrue();

    // A believable history gives the item name a monthly cost of its own.
    $this->getJson("/api/cycle-types/{$type->id}/entries")->assertOk()->assertJsonPath('type.avg_days', fn ($days) => $days >= 20 && $days <= 40);
});

it('generates sample item names, numbering one that is already in the category', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $actor = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($actor)->postJson("/api/cycle-categories/{$category->id}/types/generate", ['count' => 7]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(7);

    $types = CycleType::query()->where('category_id', $category->id)->with('entries')->get();

    expect($types->pluck('name')->unique())->toHaveCount(7)
        ->and($types->filter(fn (CycleType $type) => preg_match('/ 2$/', $type->name))->count())->toBe(2)
        ->and($types->every(fn (CycleType $type) => $type->entries->count() >= 3 && $type->entries->count() <= 5))->toBeTrue();
});

it('generates sample expense categories with item names and purchases', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/cycle-categories/generate', ['count' => 3]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3)
        ->and(Category::query()->where('type', CategoryType::Expense->value)->count())->toBe(3)
        ->and(Category::query()->where('type', CategoryType::Income->value)->count())->toBe(0)
        ->and(CycleType::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(CycleEntry::query()->count())->toBeGreaterThanOrEqual(18);

    $this->getJson('/api/cycle-categories')->assertOk()->assertJsonPath('summary.monthly_total', fn ($total) => $total > 0);

    // Asking again never duplicates a category name.
    $this->postJson('/api/cycle-categories/generate', ['count' => 12])->assertOk();
    expect(Category::query()->pluck('name')->unique())->toHaveCount(15);
});

it('validates the sample data count', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $actor = User::factory()->create();
    $type = CycleType::factory()->create();

    foreach (['/api/cycle-categories/generate', "/api/cycle-categories/{$type->category_id}/types/generate", "/api/cycle-types/{$type->id}/entries/generate"] as $url) {
        $this->actingAs($actor)->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('count');
        $this->actingAs($actor)->postJson($url, ['count' => 0])->assertUnprocessable()->assertJsonValidationErrors('count');
        $this->actingAs($actor)->postJson($url, ['count' => 51])->assertUnprocessable()->assertJsonValidationErrors('count');
    }
});

it('does not generate sample data for an income category', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $actor = User::factory()->create();
    $income = Category::factory()->income()->create();

    $this->actingAs($actor)->postJson("/api/cycle-categories/{$income->id}/types/generate", ['count' => 2])->assertNotFound();
});

it('rejects generating sample data outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');
    $actor = User::factory()->create();
    $type = CycleType::factory()->create();

    $this->actingAs($actor)->postJson('/api/cycle-categories/generate', ['count' => 2])->assertNotFound();
    $this->actingAs($actor)->postJson("/api/cycle-categories/{$type->category_id}/types/generate", ['count' => 2])->assertNotFound();
    $this->actingAs($actor)->postJson("/api/cycle-types/{$type->id}/entries/generate", ['count' => 2])->assertNotFound();
});

it('renders the usage cycle pages for a signed-in user', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/cycles')->assertOk()->assertSee('Usage Cycle');
    $this->actingAs($actor)->get('/cycles/1')->assertOk();
    $this->actingAs($actor)->get('/cycles/1/2')->assertOk();
    $this->actingAs($actor)->get('/cycles/1/2/3')->assertOk();
    $this->actingAs($actor)->get('/cycles/body-care')->assertNotFound();
});

it('redirects a guest from the usage cycle pages', function () {
    $this->get('/cycles')->assertRedirect('/login');
    $this->get('/cycles/1/2/3')->assertRedirect('/login');
});
