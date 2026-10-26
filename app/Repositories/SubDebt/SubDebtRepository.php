<?php

namespace App\Repositories\SubDebt;

use App\Models\SubDebt;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubDebtRepository implements SubDebtRepositoryInterface
{
    use RunsTransactions;

    public function paginate(int $debtId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = SubDebt::query()->where('debt_id', $debtId);

        $this->applySearch($query, $search);

        $sortable = ['name', 'amount', 'paid_amount', 'due_date', 'is_paid', 'note', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->oldest();
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function getForDebt(int $debtId): Collection
    {
        return SubDebt::query()->where('debt_id', $debtId)->orderBy('id')->get();
    }

    public function summary(int $debtId): array
    {
        $query = SubDebt::query()->where('debt_id', $debtId);

        return [
            'total' => (clone $query)->count(),
            'paid' => (clone $query)->where('is_paid', true)->count(),
            'amount_total' => (int) (clone $query)->sum('amount'),
            'paid_total' => (int) $query->sum('paid_amount'),
        ];
    }

    public function countMatching(int $debtId, ?string $search = null): int
    {
        $query = SubDebt::query()->where('debt_id', $debtId);

        $this->applySearch($query, $search);

        return $query->count();
    }

    public function deleteMatching(int $debtId, ?string $search = null): void
    {
        $query = SubDebt::query()->where('debt_id', $debtId);

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function delete(SubDebt $subDebt): void
    {
        $subDebt->delete();
    }

    public function deleteMany(int $debtId, array $ids): void
    {
        SubDebt::query()->where('debt_id', $debtId)->whereIn('id', $ids)->delete();
    }

    public function create(int $debtId, array $data): SubDebt
    {
        return SubDebt::query()->create([...$data, 'debt_id' => $debtId]);
    }

    public function update(SubDebt $subDebt, array $data): SubDebt
    {
        $subDebt->update($data);

        return $subDebt;
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%");
        });
    }
}
