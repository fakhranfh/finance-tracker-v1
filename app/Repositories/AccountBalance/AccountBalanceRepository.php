<?php

namespace App\Repositories\AccountBalance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountBalanceRepository implements AccountBalanceRepositoryInterface
{
    public function adjustLastBalance(int $accountId, string $signedAmount): void
    {
        DB::update(
            'update accounts set last_balance = coalesce(last_balance, 0) + cast(? as bigint) where id = ?',
            [$signedAmount, $accountId],
        );
    }

    /**
     * Accounts whose last_balance disagrees with their newest snapshot plus
     * the records after it. Accounts without a snapshot cannot be checked.
     */
    public function driftedAccounts(): Collection
    {
        return collect(DB::select(<<<'SQL'
            select * from (
                select a.id, a.name, a.last_balance,
                    (select s.balance from account_balance_snapshots s
                        where s.account_id = a.id order by s.last_record_id desc limit 1)
                    + coalesce((select sum(case
                            when r.type = 'income' and r.account_id = a.id then r.amount
                            when r.type = 'expense' and r.account_id = a.id then -r.amount
                            when r.type = 'transfer' and r.account_id = a.id then -r.amount
                            when r.type = 'transfer' and r.to_account_id = a.id then r.amount
                            else 0 end)
                        from records r
                        where (r.account_id = a.id or r.to_account_id = a.id)
                            and r.id > (select max(s.last_record_id) from account_balance_snapshots s where s.account_id = a.id)), 0) as actual_balance
                from accounts a
                where exists (select 1 from account_balance_snapshots s where s.account_id = a.id)
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

        return DB::table('records')
            ->where('id', '>', $watermark)
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->count();
    }

    public function lastBalance(int $accountId): ?string
    {
        return DB::table('accounts')->where('id', $accountId)->value('last_balance');
    }

    public function latestRecordId(int $accountId): ?int
    {
        return DB::table('records')
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->max('id');
    }

    public function latestSnapshot(int $accountId, ?int $beforeRecordId = null): ?array
    {
        $snapshot = DB::table('account_balance_snapshots')
            ->where('account_id', $accountId)
            ->when($beforeRecordId !== null, fn ($q) => $q->where('last_record_id', '<', $beforeRecordId))
            ->orderByDesc('last_record_id')
            ->first();

        if (! $snapshot) {
            return null;
        }

        return [
            'last_record_id' => $snapshot->last_record_id,
            'balance' => $snapshot->balance,
            'flows' => [
                'income' => $snapshot->income,
                'expense' => $snapshot->expense,
                'transfer_in' => $snapshot->transfer_in,
                'transfer_out' => $snapshot->transfer_out,
                'net' => $snapshot->net,
                'record_count' => $snapshot->record_count,
            ],
        ];
    }

    /**
     * Flow totals of the start totals plus the records in (afterRecordId, uptoRecordId].
     *
     * @param  array{income: string|float, expense: string|float, transfer_in: string|float, transfer_out: string|float, net: string|float, record_count?: int}  $start
     * @return array{income: string, expense: string, transfer_in: string, transfer_out: string, net: string, record_count: int}
     */
    public function flowsAfterRecords(int $accountId, array $start, int $afterRecordId, int $uptoRecordId): array
    {
        $row = DB::selectOne(<<<'SQL'
            select
                cast(? as bigint)
                + coalesce(sum(case when type = 'income' and account_id = ? then amount end), 0) as income,
                cast(? as bigint)
                + coalesce(sum(case when type = 'expense' and account_id = ? then amount end), 0) as expense,
                cast(? as bigint)
                + coalesce(sum(case when type = 'transfer' and to_account_id = ? then amount end), 0) as transfer_in,
                cast(? as bigint)
                + coalesce(sum(case when type = 'transfer' and account_id = ? then amount end), 0) as transfer_out,
                cast(? as bigint)
                + coalesce(sum(case
                    when type = 'income' and account_id = ? then amount
                    when type = 'expense' and account_id = ? then -amount
                    when type = 'transfer' and to_account_id = ? then amount
                    when type = 'transfer' and account_id = ? then -amount
                end), 0) as net,
                ? + count(*) as record_count
            from records
            where id > ? and id <= ? and (account_id = ? or to_account_id = ?)
            SQL, [
            $start['income'], $accountId,
            $start['expense'], $accountId,
            $start['transfer_in'], $accountId,
            $start['transfer_out'], $accountId,
            $start['net'], $accountId, $accountId, $accountId, $accountId,
            $start['record_count'] ?? 0,
            $afterRecordId, $uptoRecordId, $accountId, $accountId,
        ]);

        return [
            'income' => $row->income,
            'expense' => $row->expense,
            'transfer_in' => $row->transfer_in,
            'transfer_out' => $row->transfer_out,
            'net' => $row->net,
            'record_count' => (int) $row->record_count,
        ];
    }

    public function balanceAfterRecords(int $accountId, string $startBalance, int $afterRecordId, int $uptoRecordId): string
    {
        return DB::selectOne(<<<'SQL'
            select
                cast(? as bigint)
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

    public function storeSnapshot(int $accountId, int $lastRecordId, string $balance, array $flows): void
    {
        DB::table('account_balance_snapshots')->insertOrIgnore([
            'account_id' => $accountId,
            'last_record_id' => $lastRecordId,
            'balance' => $balance,
            'income' => $flows['income'],
            'expense' => $flows['expense'],
            'transfer_in' => $flows['transfer_in'],
            'transfer_out' => $flows['transfer_out'],
            'net' => $flows['net'],
            'record_count' => $flows['record_count'],
            'taken_at' => now(),
        ]);
    }
}
