<?php

namespace App\Repositories\AccountBalance;

use App\Models\Account;
use App\Models\Record;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountBalanceRepository implements AccountBalanceRepositoryInterface
{
    public function adjustLastBalance(int $accountId, string $signedAmount): void
    {
        DB::update(
            'update accounts set last_balance = coalesce(last_balance, initial_balance) + cast(? as decimal(14,2)) where id = ?',
            [$signedAmount, $accountId],
        );
    }

    public function driftedAccounts(): Collection
    {
        return collect(DB::select(<<<'SQL'
            select * from (
                select a.id, a.name, a.last_balance,
                    a.initial_balance
                    + coalesce((select sum(case when r.type = 'income' then r.amount else -r.amount end)
                        from records r where r.account_id = a.id), 0)
                    + coalesce((select sum(r.amount)
                        from records r where r.type = 'transfer' and r.to_account_id = a.id), 0) as actual_balance
                from accounts a
            ) t
            where t.last_balance is null or t.last_balance <> t.actual_balance
            order by t.id
            SQL));
    }

    public function setLastBalance(int $accountId, string $balance): void
    {
        DB::table('accounts')->where('id', $accountId)->update(['last_balance' => $balance]);
    }

    public function deleteSnapshots(array $accountIds, ?int $fromRecordId = null): Collection
    {
        $query = DB::table('account_balance_snapshots')
            ->whereIn('account_id', $accountIds)
            ->when($fromRecordId !== null, fn ($q) => $q->where('last_record_id', '>=', $fromRecordId));

        $affected = (clone $query)->distinct()->pluck('account_id');

        if ($affected->isNotEmpty()) {
            $query->delete();
        }

        return $affected;
    }

    public function countRecordsSinceSnapshot(int $accountId): int
    {
        $watermark = DB::table('account_balance_snapshots')->where('account_id', $accountId)->max('last_record_id') ?? 0;

        return Record::query()
            ->where('id', '>', $watermark)
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->count();
    }

    public function initialBalance(int $accountId): ?string
    {
        return Account::query()->whereKey($accountId)->toBase()->value('initial_balance');
    }

    public function latestRecordId(int $accountId): ?int
    {
        return Record::query()
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->max('id');
    }

    public function latestSnapshot(int $accountId): ?array
    {
        $snapshot = DB::table('account_balance_snapshots')
            ->where('account_id', $accountId)
            ->orderByDesc('last_record_id')
            ->first();

        return $snapshot ? ['last_record_id' => $snapshot->last_record_id, 'balance' => $snapshot->balance] : null;
    }

    public function balanceAfterRecords(int $accountId, string $startBalance, int $afterRecordId, int $uptoRecordId): string
    {
        return DB::selectOne(<<<'SQL'
            select
                cast(? as decimal(14,2))
                + coalesce((select sum(case when type = 'income' then amount else -amount end)
                    from records where account_id = ? and id > ? and id <= ?), 0)
                + coalesce((select sum(amount)
                    from records where type = 'transfer' and to_account_id = ? and id > ? and id <= ?), 0) as balance
            SQL, [
            $startBalance,
            $accountId, $afterRecordId, $uptoRecordId,
            $accountId, $afterRecordId, $uptoRecordId,
        ])->balance;
    }

    public function storeSnapshot(int $accountId, int $lastRecordId, string $balance): void
    {
        DB::table('account_balance_snapshots')->insertOrIgnore([
            'account_id' => $accountId,
            'last_record_id' => $lastRecordId,
            'balance' => $balance,
            'taken_at' => now(),
        ]);
    }
}
