<?php

namespace App\Repositories\Account;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $sortable = ['name', 'initial_balance', 'balance', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function deleteMany(array $ids): void
    {
        Account::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Account::query();

        $this->applySearch($query, $search);

        $query->delete();
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
        $account->delete();
    }
}
