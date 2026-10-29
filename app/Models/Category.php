<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Enums\RecordType;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Label for income or expense records. Deleting one keeps its records, uncategorised.
 */
#[Fillable(['name', 'type', 'color', 'icon'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToUser, HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{type: 'App\Enums\CategoryType'}
     */
    protected function casts(): array
    {
        return ['type' => CategoryType::class];
    }

    /**
     * A random category matching a record type, or null when there is none (always null for transfers).
     */
    public static function randomIdOfType(RecordType $type): ?int
    {
        return static::query()->where('type', $type->value)->inRandomOrder()->value('id');
    }

    /**
     * @return HasMany<Record, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
