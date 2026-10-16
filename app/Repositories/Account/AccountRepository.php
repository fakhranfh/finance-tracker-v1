<?php

namespace App\Repositories\Account;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountRepository implements AccountRepositoryInterface
{
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
        Account::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @return array<int, int>
     */
    public function idsMatching(?string $search = null): array
    {
        $query = Account::query();

        $this->applySearch($query, $search);

        return $query->pluck('id')->all();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Account::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
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

        return $account;
    }

    public function delete(Account $account): void
    {
        Account::query()->whereKey($account->id)->delete();
    }

    public function allIds(?int $exceptId = null): array
    {
        return Account::query()->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))->pluck('id')->all();
    }

    public function nextSortOrder(): int
    {
        return (int) (Account::query()->max('sort_order') ?? 0) + 1;
    }

    public function namesOf(array $ids): Collection
    {
        return Account::query()->whereIn('id', $ids)->pluck('name');
    }

    public function findByName(string $name): ?Account
    {
        return Account::query()->where('name', $name)->orderBy('id')->first();
    }

    public function allNamed(): Collection
    {
        return Account::query()->orderBy('name')->get(['id', 'name']);
    }
}
