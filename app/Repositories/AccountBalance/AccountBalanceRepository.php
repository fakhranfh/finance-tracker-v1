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
            'update accounts set last_balance = coalesce(last_balance, 0) + cast(? as decimal(14,2)) where id = ?',
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

        return Record::query()
            ->where('id', '>', $watermark)
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->count();
    }

    public function lastBalance(int $accountId): ?string
    {
        return Account::query()->whereKey($accountId)->toBase()->value('last_balance');
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

        if (! $snapshot) {
            return null;
        }

        return [
            'last_record_id' => $snapshot->last_record_id,
            'balance' => $snapshot->balance,
            'totals' => [
                'income' => (float) $snapshot->income,
                'expense' => (float) $snapshot->expense,
                'transfer_in' => (float) $snapshot->transfer_in,
                'transfer_out' => (float) $snapshot->transfer_out,
                'record_count' => (int) $snapshot->record_count,
            ],
        ];
    }

    public function totalsAfterRecords(int $accountId, ?array $startTotals, int $afterRecordId, ?int $uptoRecordId = null): array
    {
        $row = Record::query()
            ->where(fn ($q) => $q->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->where('id', '>', $afterRecordId)
            ->when($uptoRecordId !== null, fn ($q) => $q->where('id', '<=', $uptoRecordId))
            ->selectRaw(
                "coalesce(sum(case when type = 'income' and account_id = ? then amount end), 0) as income,
                 coalesce(sum(case when type = 'expense' and account_id = ? then amount end), 0) as expense,
                 coalesce(sum(case when type = 'transfer' and account_id = ? then amount end), 0) as transfer_out,
                 coalesce(sum(case when type = 'transfer' and to_account_id = ? then amount end), 0) as transfer_in,
                 count(*) as record_count",
                [$accountId, $accountId, $accountId, $accountId],
            )
            ->toBase()
            ->first();

        $start = $startTotals ?? ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'record_count' => 0];

        return [
            'income' => round($start['income'] + (float) $row->income, 2),
            'expense' => round($start['expense'] + (float) $row->expense, 2),
            'transfer_in' => round($start['transfer_in'] + (float) $row->transfer_in, 2),
            'transfer_out' => round($start['transfer_out'] + (float) $row->transfer_out, 2),
            'record_count' => $start['record_count'] + (int) $row->record_count,
        ];
    }

    public function currentTotals(int $accountId): array
    {
        $base = $this->latestSnapshot($accountId);

        return $this->totalsAfterRecords($accountId, $base['totals'] ?? null, $base['last_record_id'] ?? 0);
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

    public function storeSnapshot(int $accountId, int $lastRecordId, string $balance, array $totals): void
    {
        DB::table('account_balance_snapshots')->insertOrIgnore([
            'account_id' => $accountId,
            'last_record_id' => $lastRecordId,
            'balance' => $balance,
            'income' => $totals['income'],
            'expense' => $totals['expense'],
            'transfer_in' => $totals['transfer_in'],
            'transfer_out' => $totals['transfer_out'],
            'record_count' => $totals['record_count'],
            'taken_at' => now(),
        ]);
    }
}
