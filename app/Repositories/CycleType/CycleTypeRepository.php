<?php

namespace App\Repositories\CycleType;

use App\Models\CycleType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

class CycleTypeRepository implements CycleTypeRepositoryInterface
{
    public function allWithEntries(): Collection
    {
        return CycleType::query()->with('entries')->get();
    }

    public function forCategory(int $categoryId): Collection
    {
        return CycleType::query()->where('category_id', $categoryId)->with('entries')->orderBy('name')->get();
    }

    public function paginateForCategory(int $categoryId, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $query = CycleType::query()->where('category_id', $categoryId)->with('entries');

        if ($search) {
            $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']);
        }

        return $query->orderBy('name')->orderBy('id')->paginate($perPage)->withQueryString();
    }

    public function create(int $categoryId, array $data): CycleType
    {
        return CycleType::query()->create([...$data, 'category_id' => $categoryId]);
    }

    public function loadDetail(CycleType $type): CycleType
    {
        return $type->load([
            'category:id,name',
            'entries' => fn (HasMany $query) => $query->orderBy('bought_at')->orderBy('id'),
        ]);
    }
}
