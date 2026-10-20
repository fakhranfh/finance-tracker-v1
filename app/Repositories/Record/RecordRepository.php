<?php

namespace App\Repositories\Record;

use App\Enums\RecordType;
use App\Models\Record;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class RecordRepository implements RecordRepositoryInterface
{
    use RunsTransactions;

    public function find(int $id): ?Record
    {
        return Record::query()->with(['account', 'toAccount', 'category'])->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        $query = Record::query()->with(['account', 'toAccount', 'category']);

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $this->applySort($query, $sort, $direction);

        return $query->paginate($perPage)->withQueryString();
    }

    public function export(?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LazyCollection
    {
        $query = Record::query()->with(['account', 'toAccount', 'category']);

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort, $direction);

        return $query->lazy(500);
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

    public function flows(?string $search = null, array $filters = []): array
    {
        $query = Record::query();

        $this->applySearch($query, $search);
        $this->applyFilters($query, $filters);

        $accountIds = array_map('intval', $filters['account_ids'] ?? []);
        $in = $accountIds === [] ? '' : ' and to_account_id in ('.implode(',', $accountIds).')';
        $out = $accountIds === [] ? '' : ' and account_id in ('.implode(',', $accountIds).')';

        $row = $query->toBase()->reorder()
            ->selectRaw(
                "coalesce(sum(case when type = 'income' then amount end), 0) as income,
                 coalesce(sum(case when type = 'expense' then amount end), 0) as expense,
                 coalesce(sum(case when type = 'transfer'{$out} then amount end), 0) as transfer_out,
                 coalesce(sum(case when type = 'transfer'{$in} then amount end), 0) as transfer_in,
                 count(*) as record_count",
            )
            ->first();

        return [
            'income' => (int) round((float) $row->income, 0),
            'expense' => (int) round((float) $row->expense, 0),
            'transfer_in' => (int) round((float) $row->transfer_in, 0),
            'transfer_out' => (int) round((float) $row->transfer_out, 0),
            'record_count' => (int) $row->record_count,
        ];
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

    /**
     * @param  Builder<Record>  $query
     */
    private function applySort(Builder $query, ?string $sort, string $direction): void
    {
        $sortable = ['type', 'amount', 'note', 'recorded_at', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction)->orderByDesc('id');
        } else {
            $query->latest('recorded_at')->latest('id');
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
                ->orWhereHas('toAccount', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
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
            ->when($filters['category_ids'] ?? [], fn (Builder $q, array $categoryIds) => $this->applyCategoryFilter($q, $categoryIds))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('recorded_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('recorded_at', '<=', $to))
            ->when($filters['amount_min'] ?? null, fn (Builder $q, string $min) => $q->where('amount', '>=', $min))
            ->when($filters['amount_max'] ?? null, fn (Builder $q, string $max) => $q->where('amount', '<=', $max));
    }

    /**
     * "none" matches income and expense records without a category (transfers never have one).
     *
     * @param  Builder<Record>  $query
     * @param  array<int, int|string>  $categoryIds
     */
    private function applyCategoryFilter(Builder $query, array $categoryIds): void
    {
        $uncategorized = in_array('none', $categoryIds, true);
        $ids = array_values(array_filter($categoryIds, fn ($id) => $id !== 'none'));

        $query->where(fn (Builder $inner) => $inner
            ->when($ids !== [], fn (Builder $q) => $q->whereIn('category_id', $ids))
            ->when($uncategorized, fn (Builder $q) => $q->orWhere(fn (Builder $none) => $none
                ->whereNull('category_id')
                ->where('type', '!=', RecordType::Transfer->value))));
    }

    public function create(array $data): Record
    {
        return Record::query()->create($data)->load(['account', 'toAccount', 'category']);
    }

    public function update(Record $record, array $data): Record
    {
        $record->update($data);

        return $record->load(['account', 'toAccount', 'category']);
    }

    public function delete(Record $record): void
    {
        $record->delete();
    }

    public function deleteAllForAccount(int $accountId): void
    {
        Record::query()
            ->where('account_id', $accountId)
            ->orWhere('to_account_id', $accountId)
            ->chunkById(200, fn ($records) => $records->each->delete());
    }
}
