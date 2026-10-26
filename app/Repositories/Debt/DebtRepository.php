<?php

namespace App\Repositories\Debt;

use App\Models\Debt;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtRepository implements DebtRepositoryInterface
{
    use RunsTransactions;

    public function find(int $id): ?Debt
    {
        return $this->withPaid(Debt::query())->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        $query = $this->withPaid(Debt::query());

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $sortable = ['type', 'name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort === 'is_paid') {
            $query->orderByRaw('((select coalesce(sum(records.amount), 0) from records where records.debt_id = debts.id) >= debts.amount) '.$direction);
        } elseif ($sort && in_array($sort, $sortable, true)) {
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

    public function deleteMatching(?string $search = null, array $filters = []): void
    {
        $query = Debt::query();

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        $query = Debt::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Select what has been paid (the sum of the linked records) with every debt.
     *
     * @param  Builder<Debt>  $query
     * @return Builder<Debt>
     */
    private function withPaid(Builder $query): Builder
    {
        return $query->withSum('payments as paid_amount', 'amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type));
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
