<?php

use App\Enums\RecordType;
use App\Jobs\RefreshAccountBalanceSnapshot;
use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Services\AccountManagementService;
use App\Services\AccountSummaryService;
use App\Services\RecordManagementService;
use Illuminate\Support\Facades\DB;

function balanceOf(Account $account): string
{
    return Account::query()->withBalance()->find($account->id)->balance;
}

function snapshotOf(Account $account): ?object
{
    return DB::table('account_balance_snapshots')->where('account_id', $account->id)->orderByDesc('last_record_id')->first();
}

it('adds a snapshot for every record', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);

    Record::factory()->count(2)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    expect(DB::table('account_balance_snapshots')->where('account_id', $account->id)->count())->toBe(2);

    $last = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    expect(snapshotOf($account)->last_record_id)->toBe($last->id)
        ->and(snapshotOf($account)->balance)->toEqual(1300)
        ->and(balanceOf($account))->toEqual(1300);
});

it('snapshots the balance after the newest record', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 50]);

    expect(snapshotOf($account)->balance)->toEqual(1250)
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

it('replaces the newest snapshot when the newest record changes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    $newer = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    $newer->update(['amount' => 250]);

    expect(snapshotOf($account)->last_record_id)->toBe($newer->id)
        ->and(snapshotOf($account)->balance)->toEqual(550)
        ->and(balanceOf($account))->toEqual(550);
});

it('rebuilds snapshots after bulk record deletes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $records = Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);

    app(RecordManagementService::class)->deleteMany([$records[0]->id, $records[2]->id]);

    expect(snapshotOf($account)->balance)->toEqual(100)
        ->and(balanceOf($account))->toEqual(100);

    app(RecordManagementService::class)->deleteMatching();
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

    app(AccountManagementService::class)->delete($to);

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

    expect($snapshots)->toHaveCount(6)
        ->and($snapshots[2]->balance)->toEqual(1300)
        ->and($snapshots[5]->balance)->toEqual(1270)
        ->and(balanceOf($account))->toEqual(1270);
});

it('keeps accounts.last_balance in step with the newest snapshot', function () {
    $this->actingAs(User::factory()->create());
    $account = app(AccountRepositoryInterface::class)->create(['name' => 'Tunai', 'last_balance' => 1000]);
    expect($account->fresh()->last_balance)->toEqual(1000);

    $records = Record::factory()->count(3)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    expect($account->fresh()->last_balance)->toEqual(1300);

    $records[0]->update(['amount' => 400]);
    expect($account->fresh()->last_balance)->toEqual(1600);

    app(RecordManagementService::class)->deleteMatching();
    expect($account->fresh()->last_balance)->toEqual(1000);
});

it('updates last_balance from the totals as records change', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);

    $income = Record::factory()->create(['account_id' => $from->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 50]);

    expect($from->fresh()->last_balance)->toEqual(550)
        ->and($to->fresh()->last_balance)->toEqual(50);

    $income->delete();
    expect($from->fresh()->last_balance)->toEqual(450);
});

it('adjusts last_balance when a counterpart account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 500]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 100]);
    expect($from->fresh()->last_balance)->toEqual(400);

    app(AccountManagementService::class)->delete($to);
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

it('stores cumulative income, expense and transfers in and out with each snapshot', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    $other = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->count(2)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 30]);
    Record::factory()->create(['account_id' => $account->id, 'to_account_id' => $other->id, 'type' => RecordType::Transfer, 'amount' => 50]);
    Record::factory()->create(['account_id' => $other->id, 'to_account_id' => $account->id, 'type' => RecordType::Transfer, 'amount' => 20]);

    RefreshAccountBalanceSnapshot::dispatchSync($account->id);

    $snapshot = snapshotOf($account);
    expect((float) $snapshot->income)->toEqual(200)
        ->and((float) $snapshot->expense)->toEqual(30)
        ->and((float) $snapshot->transfer_in)->toEqual(20)
        ->and((float) $snapshot->transfer_out)->toEqual(50)
        ->and((float) $snapshot->net)->toEqual(140)
        ->and((float) $snapshot->balance)->toEqual(1140);
});

it('fills the snapshot and totals from the records when the all-time summary finds nothing stored', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->count(2)->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100, 'recorded_at' => '2026-02-10 10:00:00']);
    DB::table('account_balance_snapshots')->delete();
    DB::table('account_monthly_totals')->delete();
    DB::table('account_yearly_totals')->delete();

    $totals = app(AccountSummaryService::class)->allTime($account->id);

    expect($totals['income'])->toEqual(200)
        ->and($totals['record_count'])->toBe(2)
        ->and(snapshotOf($account)->balance)->toEqual(1200)
        ->and(DB::table('account_monthly_totals')->where('account_id', $account->id)->count())->toBe(1)
        ->and($account->fresh()->last_balance)->toEqual(1200);
});
