<?php

namespace App\Repositories\Record;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordRepository implements RecordRepositoryInterface
{
    public function find(int $id): ?Record
    {
        return Record::query()->with(['account', 'toAccount'])->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Record::query()->with(['account', 'toAccount']);

        $this->applySearch($query, $search);

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
        Record::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Record::query();

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Record::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
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
