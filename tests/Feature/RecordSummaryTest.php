<?php

use App\Models\Account;
use App\Models\Record;
use App\Models\User;

it('rejects a guest from the records summary', function () {
    $this->getJson('/api/records/summary')->assertStatus(401);
});

it('sums income, expense and transfers of all records from the stored totals', function () {
    $user = User::factory()->create();
    $a = Account::factory()->create();
    $b = Account::factory()->create();
    Record::factory()->create(['account_id' => $a->id, 'type' => 'income', 'amount' => 5000]);
    Record::factory()->create(['account_id' => $a->id, 'type' => 'income', 'amount' => 2500]);
    Record::factory()->create(['account_id' => $a->id, 'type' => 'expense', 'amount' => 3000]);
    Record::factory()->create(['account_id' => $a->id, 'to_account_id' => $b->id, 'type' => 'transfer', 'amount' => 1000]);

    $this->actingAs($user)->getJson('/api/records/summary')
        ->assertOk()->assertJson(['data' => [
            'income' => 7500,
            'expense' => 3000,
            'transfer_in' => 1000,
            'transfer_out' => 1000,
            'net' => 4500,
            'record_count' => 4,
        ]]);
});

it('summarizes a date range and accounts like the account summary', function () {
    $user = User::factory()->create();
    $a = Account::factory()->create();
    $b = Account::factory()->create();
    Record::factory()->create(['account_id' => $a->id, 'type' => 'income', 'amount' => 5000, 'recorded_at' => '2026-03-10 05:00:00']);
    Record::factory()->create(['account_id' => $a->id, 'type' => 'expense', 'amount' => 800, 'recorded_at' => '2026-03-12 05:00:00']);
    Record::factory()->create(['account_id' => $a->id, 'type' => 'expense', 'amount' => 999, 'recorded_at' => '2026-04-12 05:00:00']);
    Record::factory()->create(['account_id' => $b->id, 'type' => 'income', 'amount' => 777, 'recorded_at' => '2026-03-11 05:00:00']);
    Record::factory()->create(['account_id' => $b->id, 'to_account_id' => $a->id, 'type' => 'transfer', 'amount' => 300, 'recorded_at' => '2026-03-15 05:00:00']);

    $this->actingAs($user)
        ->getJson("/api/records/summary?account_ids[]={$a->id}&date_from=2026-03-01&date_to=2026-03-31")
        ->assertOk()->assertJson(['data' => [
            'income' => 5000,
            'expense' => 800,
            'transfer_in' => 300,
            'transfer_out' => 0,
            'net' => 4500,
            'record_count' => 3,
        ]]);
});

it('reads the records themselves for type, search and amount filters', function () {
    $user = User::factory()->create();
    Record::factory()->create(['type' => 'income', 'amount' => 5000, 'note' => 'Gaji']);
    Record::factory()->create(['type' => 'expense', 'amount' => 3000, 'note' => 'Kopi']);

    $this->actingAs($user)->getJson('/api/records/summary?type[]=expense')
        ->assertOk()->assertJson(['data' => ['income' => 0, 'expense' => 3000, 'net' => -3000, 'record_count' => 1]]);

    $this->actingAs($user)->getJson('/api/records/summary?search=Gaji')
        ->assertOk()->assertJson(['data' => ['income' => 5000, 'expense' => 0, 'record_count' => 1]]);
});

it('validates the filters', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/records/summary?type[]=bogus')->assertStatus(422);
});

it('returns zeros when there are no records', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/records/summary')
        ->assertOk()->assertJson(['data' => ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'net' => 0, 'record_count' => 0]]);
});
