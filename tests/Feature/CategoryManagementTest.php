<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing categories', function () {
    $this->getJson('/api/categories')->assertStatus(401);
});

it('lists categories via the api', function () {
    $actor = User::factory()->create();
    Category::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/categories');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'type', 'color', 'icon', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches categories via the api', function () {
    $actor = User::factory()->create();
    Category::factory()->create(['name' => 'Findable entry']);
    Category::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/categories?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a category via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/categories', [
        'name' => 'Sample name',
        'type' => 'expense',
        'color' => '#10B981',
        'icon' => 'cart',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $this->assertDatabaseHas('categories', ['name' => 'Sample name']);
});

it('rejects creating a category with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/categories', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'type', 'color', 'icon']);
});

it('shows a category via the api', function () {
    $actor = User::factory()->create();
    $target = Category::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/categories/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a category via the api', function () {
    $actor = User::factory()->create();
    $target = Category::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/categories/{$target->id}", [
        'name' => 'Updated name',
        'type' => 'income',
        'color' => '#EF4444',
        'icon' => 'gift',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated name');
});

it('deletes a category via the api', function () {
    $actor = User::factory()->create();
    $target = Category::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/categories/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('categories', ['id' => $target->id]);
});

it('bulk deletes categories by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Category::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/categories', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Category::query()->count())->toBe(1);
});

it('bulk deletes every category when all is set', function () {
    $actor = User::factory()->create();
    Category::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/categories', ['all' => true])->assertNoContent();

    expect(Category::query()->count())->toBe(0);
});

it('generates sample categories via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/categories/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Category::query()->count())->toBe(5);
});

it('rejects generating sample categories outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/categories/generate', ['count' => 5])->assertNotFound();
});

it('renders the category pages', function () {
    $actor = User::factory()->create();

    foreach (['/categories', '/categories/create', '/categories/1/edit'] as $url) {
        $this->actingAs($actor)->get($url)->assertOk();
    }
});

it('validates category type, color, icon and per-type name uniqueness', function () {
    $actor = User::factory()->create();
    Category::factory()->create(['name' => 'Groceries', 'type' => 'expense']);
    $payload = ['name' => 'Groceries', 'type' => 'expense', 'color' => '#10B981', 'icon' => 'cart'];

    $this->actingAs($actor)->postJson('/api/categories', [...$payload, 'type' => 'transfer'])->assertJsonValidationErrors('type');
    $this->actingAs($actor)->postJson('/api/categories', [...$payload, 'color' => 'red', 'icon' => 'nope'])->assertJsonValidationErrors(['color', 'icon']);
    $this->actingAs($actor)->postJson('/api/categories', $payload)->assertJsonValidationErrors('name');
    $this->actingAs($actor)->postJson('/api/categories', [...$payload, 'type' => 'income'])->assertCreated();
});

it('locks the type of a category that has records', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create(['type' => 'expense']);
    Record::factory()->create(['category_id' => $category->id]);
    $payload = ['name' => 'Renamed', 'type' => 'expense', 'color' => '#10B981', 'icon' => 'cart'];

    $this->actingAs($actor)->putJson("/api/categories/{$category->id}", [...$payload, 'type' => 'income'])
        ->assertJsonValidationErrors('type');
    $this->actingAs($actor)->putJson("/api/categories/{$category->id}", $payload)->assertOk();
});

it('keeps records, uncategorised, when their category is deleted', function () {
    $actor = User::factory()->create();
    $category = Category::factory()->create();
    $record = Record::factory()->create(['category_id' => $category->id]);

    $this->actingAs($actor)->deleteJson("/api/categories/{$category->id}")->assertNoContent();

    expect($record->fresh()->category_id)->toBeNull();
});

it('generates sample categories of both types', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $this->actingAs(User::factory()->create())->postJson('/api/categories/generate', ['count' => 6])->assertOk();

    expect(Category::where('type', 'income')->count())->toBe(3);
});

it('saves a record with a category of the same type', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create(['name' => 'Food', 'type' => 'expense']);

    $this->actingAs($actor)->postJson('/api/records', [
        'type' => 'expense', 'account_id' => $account->id, 'category_id' => $category->id,
        'amount' => 10, 'recorded_at' => '2026-10-01T10:30:00+07:00',
    ])->assertCreated()->assertJsonPath('data.category', 'Food');
});

it('rejects a record category of another type or on a transfer', function () {
    $actor = User::factory()->create();
    [$from, $to] = Account::factory()->count(2)->create();
    $income = Category::factory()->income()->create();
    $payload = ['account_id' => $from->id, 'category_id' => $income->id, 'amount' => 10, 'recorded_at' => '2026-10-01T10:30:00+07:00'];

    $this->actingAs($actor)->postJson('/api/records', $payload + ['type' => 'expense'])->assertJsonValidationErrors('category_id');
    $this->actingAs($actor)->postJson('/api/records', $payload + ['type' => 'transfer', 'to_account_id' => $to->id])->assertJsonValidationErrors('category_id');
});

it('filters and searches records by category', function () {
    $actor = User::factory()->create();
    $food = Category::factory()->create(['name' => 'Food']);
    $match = Record::factory()->create(['category_id' => $food->id]);
    Record::factory()->create();

    expect($this->actingAs($actor)->getJson("/api/records?category_ids[]={$food->id}")->json('data.*.id'))->toBe([$match->id]);
    expect($this->actingAs($actor)->getJson('/api/records?search=Food')->json('data.*.id'))->toBe([$match->id]);
});

it('includes the category in exports', function () {
    $category = Category::factory()->create(['name' => 'Food']);
    Record::factory()->create(['category_id' => $category->id]);

    $this->actingAs(User::factory()->create())->get('/api/records/export/xlsx?category_ids[]='.$category->id)->assertOk();
    $this->actingAs(User::factory()->create())->get('/api/records/export/pdf')->assertOk();
});

it('generates realistic sample category names with matching icons', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/categories/generate', ['count' => 20])->assertOk();
    $this->actingAs($user)->postJson('/api/categories/generate', ['count' => 4])->assertOk();

    $presets = collect(config('category.samples'))->pluck('name');
    expect(Category::count())->toBe(24);
    Category::all()->each(fn (Category $category) => expect($presets->contains(fn (string $name) => str_starts_with($category->name, $name)))->toBeTrue());
    expect(Category::select('name', 'type')->distinct()->count())->toBe(24);
});

it('assigns a matching category to generated sample records', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $user = User::factory()->create();
    Category::factory()->count(2)->create(['type' => 'expense']);
    Category::factory()->count(2)->income()->create();

    $this->actingAs($user)->postJson('/api/records/generate', ['count' => 12])->assertOk();

    Record::with('category')->get()->each(function (Record $record) {
        if ($record->type->value === 'transfer') {
            expect($record->category_id)->toBeNull();
        } else {
            expect($record->category?->type->value)->toBe($record->type->value);
        }
    });
});

it('filters records without a category, leaving out transfers', function () {
    $actor = User::factory()->create();
    $food = Category::factory()->create();
    [$from, $to] = Account::factory()->count(2)->create();
    $categorized = Record::factory()->create(['category_id' => $food->id]);
    $plain = Record::factory()->create(['type' => 'expense']);
    Record::factory()->create(['type' => 'transfer', 'account_id' => $from->id, 'to_account_id' => $to->id]);

    expect($this->actingAs($actor)->getJson('/api/records?category_ids[]=none')->json('data.*.id'))->toBe([$plain->id]);

    $both = $this->actingAs($actor)->getJson("/api/records?category_ids[]=none&category_ids[]={$food->id}")->json('data.*.id');
    expect($both)->toEqualCanonicalizing([$plain->id, $categorized->id]);

    $this->actingAs($actor)->getJson('/api/records?category_ids[]=abc')->assertJsonValidationErrors('category_ids.0');
    $this->actingAs($actor)->get('/api/records/export/xlsx?category_ids[]=none')->assertOk();
});
