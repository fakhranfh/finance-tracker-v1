<?php

use App\Enums\RecordType;
use App\Jobs\RefreshAccountBalanceSnapshot;
use App\Models\Account;
use App\Models\Record;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Support\Facades\DB;

function balanceOf(Account $account): string
{
    return Account::query()->withBalance()->find($account->id)->balance;
}

function snapshotOf(Account $account): ?object
{
    return DB::table('account_balance_snapshots')->where('account_id', $account->id)->orderByDesc('last_record_id')->first();
}

beforeEach(function () {
    config(['balance.snapshot_threshold' => 3]);
});

it('snapshots automatically once enough records pile up', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);

    Record::factory()->count(2)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    expect(snapshotOf($account))->toBeNull();

    $last = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    expect(snapshotOf($account)->last_record_id)->toBe($last->id)
        ->and(snapshotOf($account)->balance)->toEqual(1300)
        ->and(balanceOf($account))->toEqual(1300);
});

it('adds records newer than the snapshot on top of it', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 50]);

    expect(snapshotOf($account)->balance)->toEqual(1300)
        ->and(balanceOf($account))->toEqual(1250);
});

it('counts transfers in and out across snapshots', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create([
        'account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 100,
    ]);

    expect(snapshotOf($from)->balance)->toEqual(200)
        ->and(balanceOf($from))->toEqual(200)
        ->and(balanceOf($to))->toEqual(300)
        ->and(snapshotOf($to)->balance)->toEqual(300);
});

it('rebuilds the snapshot when an old record changes or is deleted', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $records = Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    $records[0]->update(['amount' => 400]);
    expect(snapshotOf($account)->balance)->toEqual(600)
        ->and(balanceOf($account))->toEqual(600);

    $records[1]->delete();
    expect(snapshotOf($account)->balance)->toEqual(500)
        ->and(balanceOf($account))->toEqual(500);
});

it('keeps the snapshot when a record newer than it changes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    $newer = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    $before = snapshotOf($account);

    Queue::fake();
    $newer->update(['amount' => 250]);

    Queue::assertNothingPushed();
    expect(snapshotOf($account))->toEqual($before)
        ->and(balanceOf($account))->toEqual(550);
});

it('rebuilds snapshots after bulk record deletes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $records = Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    app(RecordRepositoryInterface::class)->deleteMany([$records[0]->id, $records[2]->id]);

    expect(snapshotOf($account)->balance)->toEqual(100)
        ->and(balanceOf($account))->toEqual(100);

    app(RecordRepositoryInterface::class)->deleteMatching();
    expect(snapshotOf($account))->toBeNull()
        ->and(balanceOf($account))->toEqual(0);
});

it('rebuilds the other side of a transfer when an account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create([
        'account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 100,
    ]);
    expect(balanceOf($from))->toEqual(200);

    app(AccountRepositoryInterface::class)->delete($to);

    expect(balanceOf($from))->toEqual(500);
});

it('refreshes a snapshot through the job', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 70]);

    RefreshAccountBalanceSnapshot::dispatchSync($account->id);

    expect(snapshotOf($account)->balance)->toEqual(70);
});

it('appends a new snapshot instead of overwriting the previous one', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 10]);

    $snapshots = DB::table('account_balance_snapshots')->where('account_id', $account->id)->orderBy('last_record_id')->get();

    expect($snapshots)->toHaveCount(2)
        ->and($snapshots[0]->balance)->toEqual(1300)
        ->and($snapshots[1]->balance)->toEqual(1270)
        ->and(balanceOf($account))->toEqual(1270);
});

it('keeps accounts.last_balance in step with the newest snapshot', function () {
    $account = app(AccountRepositoryInterface::class)->create(['name' => 'Tunai', 'last_balance' => 1000]);
    expect($account->fresh()->last_balance)->toEqual(1000);

    $records = Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    expect($account->fresh()->last_balance)->toEqual(1300);

    $records[0]->update(['amount' => 400]);
    expect($account->fresh()->last_balance)->toEqual(1600);

    app(RecordRepositoryInterface::class)->deleteMatching();
    expect($account->fresh()->last_balance)->toEqual(1000);
});

it('updates last_balance right away, before any snapshot exists', function () {
    config(['balance.snapshot_threshold' => 100]);
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);

    $income = Record::factory()->create(['account_id' => $from->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 50]);

    expect(snapshotOf($from))->toBeNull()
        ->and($from->fresh()->last_balance)->toEqual(550)
        ->and($to->fresh()->last_balance)->toEqual(50);

    $income->delete();
    expect($from->fresh()->last_balance)->toEqual(450);
});

it('adjusts last_balance when a counterpart account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 100]);
    expect($from->fresh()->last_balance)->toEqual(400);

    app(AccountRepositoryInterface::class)->delete($to);
    expect($from->fresh()->last_balance)->toEqual(500);
});

it('reports and fixes drifted last balances with the reconcile command', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    RefreshAccountBalanceSnapshot::dispatchSync($account->id);

    $this->artisan('accounts:reconcile-balances')->assertSuccessful();

    DB::table('accounts')->where('id', $account->id)->update(['last_balance' => 5]);

    $this->artisan('accounts:reconcile-balances')->assertFailed();
    expect($account->fresh()->last_balance)->toEqual(5);

    $this->artisan('accounts:reconcile-balances', ['--fix' => true])->assertSuccessful();
    expect($account->fresh()->last_balance)->toEqual(1100);
});

it('stores flow totals in the snapshot and serves the all-time summary from it', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create(['account_id' => $from->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $from->id, 'type' => RecordType::Expense, 'amount' => 40]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 25]);

    $snapshot = snapshotOf($from);
    // Snapshot taken at the third record, so it covers only the three incomes.
    expect((float) $snapshot->income)->toEqual(300)
        ->and((float) $snapshot->expense)->toEqual(0)
        ->and((float) $snapshot->transfer_out)->toEqual(0)
        ->and((float) $snapshot->transfer_in)->toEqual(0)
        ->and($snapshot->record_count)->toBe(3);

    Record::factory()->create(['account_id' => $from->id, 'type' => RecordType::Income, 'amount' => 10]);

    $totals = app(AccountSummaryRepositoryInterface::class)->totalsAll($from->id);
    expect($totals)->toMatchArray(['income' => 310.0, 'expense' => 40.0, 'transfer_out' => 25.0, 'transfer_in' => 0.0, 'record_count' => 6]);
});
