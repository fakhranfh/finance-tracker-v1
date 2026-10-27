<?php

namespace App\Repositories\DebtPayment;

use App\Models\DebtPayment;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DebtPaymentRepository implements DebtPaymentRepositoryInterface
{
    use RunsTransactions;

    public function paginateForDebt(int $debtId, ?int $subDebtId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $records = DB::table('records')
            ->leftJoin('accounts', 'accounts.id', '=', 'records.account_id')
            ->leftJoin('categories', 'categories.id', '=', 'records.category_id')
            ->leftJoin('sub_debts', 'sub_debts.id', '=', 'records.sub_debt_id')
            ->where('records.debt_id', $debtId)
            ->when($subDebtId, fn (Builder $q, int $id) => $q->where('records.sub_debt_id', $id))
            ->selectRaw("'record' as source, records.id, records.debt_id, records.sub_debt_id, sub_debts.name as sub_debt, accounts.name as account, categories.name as category, records.amount, records.note, records.recorded_at, records.created_at");

        $unrecorded = DB::table('debt_payments')
            ->leftJoin('sub_debts', 'sub_debts.id', '=', 'debt_payments.sub_debt_id')
            ->where('debt_payments.debt_id', $debtId)
            ->when($subDebtId, fn (Builder $q, int $id) => $q->where('debt_payments.sub_debt_id', $id))
            ->selectRaw("'payment' as source, debt_payments.id, debt_payments.debt_id, debt_payments.sub_debt_id, sub_debts.name as sub_debt, null as account, null as category, debt_payments.amount, debt_payments.note, debt_payments.paid_at as recorded_at, debt_payments.created_at");

        $query = DB::query()->fromSub($records->unionAll($unrecorded), 'payments');

        if ($search) {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('note', 'like', "%{$search}%")
                    ->orWhere('account', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('sub_debt', 'like', "%{$search}%");
            });
        }

        $sortable = ['amount', 'note', 'recorded_at', 'created_at'];

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc')->orderByDesc('id');
        } else {
            $query->orderByDesc('recorded_at')->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForDebt(int $debtId, int $id): ?DebtPayment
    {
        return DebtPayment::query()->with('subDebt')->where('debt_id', $debtId)->find($id);
    }

    public function create(array $data): DebtPayment
    {
        return DebtPayment::query()->create($data)->load('subDebt');
    }

    public function delete(DebtPayment $payment): void
    {
        $payment->delete();
    }
}
