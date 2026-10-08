<?php

use App\Enums\RecordType;
use App\Jobs\SyncRecordEffects;
use App\Models\Account;
use App\Models\Record;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('queues one job per account however many records change', function () {
    Queue::fake();
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);

    foreach (range(1, 5) as $i) {
        Record::factory()->create(['account_id' => $account->id, 'to_account_id' => $other->id, 'type' => RecordType::Transfer, 'amount' => $i]);
    }

    Queue::assertPushed(SyncRecordEffects::class, 2);
});

it('syncs everything that piled up in one run', function () {
    Queue::fake();
    $account = Account::factory()->create(['last_balance' => 0]);

    $records = collect(range(1, 3))->map(fn ($i) => Record::factory()->create([
        'account_id' => $account->id, 'to_account_id' => null, 'type' => RecordType::Income, 'amount' => 100 * $i, 'recorded_at' => '2026-02-10 10:00:00',
    ]));
    $records->first()->delete();

    Queue::assertPushed(SyncRecordEffects::class, 1);

    app()->call([new SyncRecordEffects($account->id), 'handle']);

    $totals = app(AccountSummaryRepositoryInterface::class)->totalsAllFromTotals($account->id);
    expect($totals['income'])->toBe(500.0)->and($totals['record_count'])->toBe(2);
    expect((float) $account->fresh()->last_balance)->toBe(500.0);
    // The deleted record's snapshot is gone; the surviving new records keep one each.
    expect(DB::table('account_balance_snapshots')->where('account_id', $account->id)->pluck('last_record_id')->all())
        ->toContain($records[1]->id, $records[2]->id)->not->toContain($records[0]->id);
});

it('queues a new job once the pending changes were taken', function () {
    Queue::fake();
    $account = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->create(['account_id' => $account->id, 'to_account_id' => null, 'type' => RecordType::Income, 'amount' => 5]);
    app()->call([new SyncRecordEffects($account->id), 'handle']);
    Record::factory()->create(['account_id' => $account->id, 'to_account_id' => null, 'type' => RecordType::Income, 'amount' => 5]);

    Queue::assertPushed(SyncRecordEffects::class, 2);
});
