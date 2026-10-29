<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * For rows that belong to a user through a parent (a record through its account): while someone is signed in,
 * only rows whose parent is visible to them (see BelongsToUser) are returned.
 */
trait BelongsToOwnerThrough
{
    /**
     * The parent model class and the foreign key pointing at it.
     *
     * @return array{0: class-string<Model>, 1: string}
     */
    abstract protected static function ownerParent(): array;

    public static function bootBelongsToOwnerThrough(): void
    {
        static::addGlobalScope('owner', function (Builder $query): void {
            if (Auth::id() === null) {
                return;
            }

            [$parent, $foreignKey] = static::ownerParent();
            $parentModel = new $parent;

            $query->whereIn(
                $query->getModel()->getTable().'.'.$foreignKey,
                $parent::query()->select($parentModel->getQualifiedKeyName()),
            );
        });
    }
}
