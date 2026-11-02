<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\CycleTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of product the user buys again and again inside an expense category, e.g. "Sabun Mandi" under "Body Care".
 */
#[Fillable(['category_id', 'name', 'icon'])]
class CycleType extends Model
{
    /** @use HasFactory<CycleTypeFactory> */
    use BelongsToUser, HasFactory, HasUserTimezone;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<CycleEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CycleEntry::class);
    }
}
