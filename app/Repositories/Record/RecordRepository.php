<?php

namespace App\Repositories\Record;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordRepository implements RecordRepositoryInterface
{
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

    /**
     * Transfers that link the given accounts to accounts outside them, as seen from those outside accounts.
     *
     * @param  array<int, int>  $accountIds
     * @return array{counterparts: array<int, int|null>, transfers_out: Collection<int, \stdClass>, transfers_in: Collection<int, \stdClass>}
     */
    public function sharedTransfers(array $accountIds): array
    {
        $counterparts = [
            ...Record::query()->whereIn('account_id', $accountIds)->distinct()->pluck('to_account_id'),
            ...Record::query()->whereIn('to_account_id', $accountIds)->distinct()->pluck('account_id'),
        ];

        return [
            'counterparts' => $counterparts,
            'transfers_out' => DB::table('records')->where('type', 'transfer')->whereIn('account_id', $accountIds)->whereNotIn('to_account_id', $accountIds)
                ->selectRaw('to_account_id, sum(amount) as total')->groupBy('to_account_id')->get(),
            'transfers_in' => DB::table('records')->where('type', 'transfer')->whereIn('to_account_id', $accountIds)->whereNotIn('account_id', $accountIds)
                ->selectRaw('account_id, sum(amount) as total')->groupBy('account_id')->get(),
        ];
    }

    public function deleteMany(array $ids): array
    {
        return $this->deleteAndDescribe(Record::query()->whereIn('id', $ids));
    }

    public function deleteMatching(?string $search = null, array $filters = []): array
    {
        $query = Record::query();

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        return $this->deleteAndDescribe($query);
    }

    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        $query = Record::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Bulk deletes skip model events, so describe what they removed for the caller to apply.
     *
     * @param  Builder<Record>  $query
     * @return array{account_ids: array<int, int|null>, first_id: ?int, effects: Collection<int, \stdClass>}
     */
    private function deleteAndDescribe(Builder $query): array
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

        return ['account_ids' => $accountIds, 'first_id' => $firstId, 'effects' => $effects];
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
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('recorded_at', '<=', $to))
            ->when($filters['amount_min'] ?? null, fn (Builder $q, string $min) => $q->where('amount', '>=', $min))
            ->when($filters['amount_max'] ?? null, fn (Builder $q, string $max) => $q->where('amount', '<=', $max));
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
