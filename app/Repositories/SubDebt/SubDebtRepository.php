<?php

namespace App\Repositories\SubDebt;

use App\Models\SubDebt;
use App\Repositories\Concerns\PaidSql;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubDebtRepository implements SubDebtRepositoryInterface
{
    use RunsTransactions;

    public function paginate(int $debtId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = $this->withPaid(SubDebt::query())->where('debt_id', $debtId);

        $this->applySearch($query, $search);

        $sortable = ['name', 'amount', 'paid_amount', 'due_date', 'is_paid', 'note', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort === 'is_paid') {
            $query->orderByRaw('(sub_debts.forgiven_at is not null or ('.PaidSql::subDebt().') >= sub_debts.amount) '.$direction);
        } elseif ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->oldest();
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function getForDebt(int $debtId): Collection
    {
        return $this->withPaid(SubDebt::query())->where('debt_id', $debtId)->orderBy('id')->get();
    }

    public function summary(int $debtId): array
    {
        $subDebts = $this->getForDebt($debtId);

        return [
            'total' => $subDebts->count(),
            'paid' => $subDebts->filter(fn (SubDebt $subDebt) => $subDebt->is_paid)->count(),
            'amount_total' => (int) $subDebts->sum('amount'),
            'paid_total' => (int) $subDebts->sum('paid_amount'),
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

    public function forgive(SubDebt $subDebt): SubDebt
    {
        $subDebt->forceFill(['forgiven_at' => now()])->save();

        return $subDebt;
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

    /**
     * Select what has been paid (the linked records and the payments without a record) with every sub debt.
     *
     * @param  Builder<SubDebt>  $query
     * @return Builder<SubDebt>
     */
    private function withPaid(Builder $query): Builder
    {
        return $query->select('sub_debts.*')->selectRaw(PaidSql::subDebt().' as paid_amount');
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
