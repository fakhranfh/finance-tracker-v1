<?php

namespace App\Repositories\Record;

use App\Models\Record;
use App\Services\AccountBalanceSnapshotService;
use App\Services\AccountLastBalanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordRepository implements RecordRepositoryInterface
{
    public function __construct(
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountLastBalanceService $lastBalance,
    ) {}

    public function find(int $id): ?Record
    {
        return Record::query()->with(['account', 'toAccount'])->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        $query = Record::query()->with(['account', 'toAccount']);

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $sortable = ['type', 'amount', 'note', 'recorded_at', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction)->orderByDesc('id');
        } else {
            $query->latest('recorded_at')->latest('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function deleteMany(array $ids): void
    {
        $this->deleteInvalidating(Record::query()->whereIn('id', $ids));
    }

    public function deleteMatching(?string $search = null, array $filters = []): void
    {
        $query = Record::query();

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $this->deleteInvalidating($query);
    }

    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        $query = Record::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Bulk deletes skip model events, so invalidate snapshots by hand.
     *
     * @param  Builder<Record>  $query
     */
    private function deleteInvalidating(Builder $query): void
    {
        $accountIds = [
            ...(clone $query)->distinct()->pluck('account_id'),
            ...(clone $query)->distinct()->pluck('to_account_id'),
        ];
        $firstId = (clone $query)->min('id');

        $effects = (clone $query)->toBase()->reorder()
            ->selectRaw('type, account_id, to_account_id, sum(amount) as total')
            ->groupBy('type', 'account_id', 'to_account_id')
            ->get();

        $query->delete();

        foreach ($effects as $effect) {
            $this->lastBalance->apply($effect->type, $effect->account_id, $effect->to_account_id, $effect->total, -1);
        }

        if ($firstId !== null) {
            $this->snapshots->invalidate($accountIds, $firstId);
        }
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('note', 'like', "%{$search}%")
                ->orWhereHas('account', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('toAccount', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        });
    }

    /**
     * An account filter matches both sides of a transfer.
     *
     * @param  Builder<Record>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['type'] ?? [], fn (Builder $q, array $types) => $q->whereIn('type', $types))
            ->when($filters['account_ids'] ?? [], fn (Builder $q, array $accountIds) => $q->where(fn (Builder $inner) => $inner
                ->whereIn('account_id', $accountIds)
                ->orWhereIn('to_account_id', $accountIds)))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('recorded_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('recorded_at', '<=', $to));
    }

    public function create(array $data): Record
    {
        return Record::query()->create($data)->load(['account', 'toAccount']);
    }

    public function update(Record $record, array $data): Record
    {
        $record->update($data);

        return $record->load(['account', 'toAccount']);
    }

    public function delete(Record $record): void
    {
        $record->delete();
    }
}
