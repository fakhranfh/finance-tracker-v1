<?php

namespace App\Repositories\Account;

use App\Models\Account;
use App\Models\Record;
use App\Services\AccountBalanceSnapshotService;
use App\Services\AccountLastBalanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountLastBalanceService $lastBalance,
    ) {}

    public function find(int $id): ?Account
    {
        return Account::query()->withBalance()->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Account::query()->withBalance();

        $this->applySearch($query, $search);

        $sortable = ['name', 'balance', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('sort_order')->orderBy('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Give the given accounts the sort slots they currently occupy, in the
     * order of $ids. Accounts not listed keep their positions.
     *
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $slots = Account::query()
                ->whereIn('id', $ids)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('sort_order')
                ->values();

            foreach ($ids as $index => $id) {
                Account::query()->whereKey($id)->update(['sort_order' => $slots[$index]]);
            }
        });
    }

    public function deleteMany(array $ids): void
    {
        $this->deleteInvalidating(Account::query()->whereIn('id', $ids));
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Account::query();

        $this->applySearch($query, $search);

        $this->deleteInvalidating($query);
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Account::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
    }

    /**
     * Deleting accounts cascades to the transfers they share with other
     * accounts, which makes those accounts' snapshots stale.
     *
     * @param  Builder<Account>  $query
     */
    private function deleteInvalidating(Builder $query): void
    {
        $ids = (clone $query)->pluck('id');

        $counterparts = [
            ...Record::query()->whereIn('account_id', $ids)->distinct()->pluck('to_account_id'),
            ...Record::query()->whereIn('to_account_id', $ids)->distinct()->pluck('account_id'),
        ];

        $transfersOut = Record::query()->where('type', 'transfer')->whereIn('account_id', $ids)->whereNotIn('to_account_id', $ids)
            ->toBase()->selectRaw('to_account_id, sum(amount) as total')->groupBy('to_account_id')->get();
        $transfersIn = Record::query()->where('type', 'transfer')->whereIn('to_account_id', $ids)->whereNotIn('account_id', $ids)
            ->toBase()->selectRaw('account_id, sum(amount) as total')->groupBy('account_id')->get();

        $query->delete();

        foreach ($transfersOut as $transfer) {
            $this->lastBalance->adjust($transfer->to_account_id, $transfer->total, -1);
        }
        foreach ($transfersIn as $transfer) {
            $this->lastBalance->adjust($transfer->account_id, $transfer->total);
        }

        $this->snapshots->invalidate($counterparts);
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%");
        });
    }

    public function create(array $data): Account
    {
        return Account::query()->create($data);
    }

    public function update(Account $account, array $data): Account
    {
        $account->update($data);

        if ($account->wasChanged('last_balance')) {
            $this->snapshots->invalidate([$account->id]);
        }

        return $account;
    }

    public function delete(Account $account): void
    {
        $this->deleteInvalidating(Account::query()->whereKey($account->id));
    }
}
