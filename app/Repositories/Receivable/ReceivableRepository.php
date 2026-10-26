<?php

namespace App\Repositories\Receivable;

use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceivableRepository implements ReceivableRepositoryInterface
{
    public function find(int $id): ?Receivable
    {
        return Receivable::query()->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Receivable::query();

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
        Receivable::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Receivable::query();

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Receivable::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

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

    public function create(array $data): Receivable
    {
        return Receivable::query()->create($data);
    }

    public function update(Receivable $receivable, array $data): Receivable
    {
        $receivable->update($data);

        return $receivable;
    }

    public function delete(Receivable $receivable): void
    {
        $receivable->delete();
    }
}
