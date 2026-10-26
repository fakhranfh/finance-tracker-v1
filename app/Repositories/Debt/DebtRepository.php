<?php

namespace App\Repositories\Debt;

use App\Models\Debt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtRepository implements DebtRepositoryInterface
{
    public function find(int $id): ?Debt
    {
        return Debt::query()->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Debt::query();

        $this->applySearch($query, $search);

        $sortable = ['name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid', 'created_at'];
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
        Debt::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Debt::query();

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Debt::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    public function create(array $data): Debt
    {
        return Debt::query()->create($data);
    }

    public function update(Debt $debt, array $data): Debt
    {
        $debt->update($data);

        return $debt;
    }

    public function delete(Debt $debt): void
    {
        $debt->delete();
    }
}
