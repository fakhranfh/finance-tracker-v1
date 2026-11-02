<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwnerThrough;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\CycleEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One purchase of a product: when it was bought, what it cost and when it ran out (null while still in use).
 */
#[Fillable(['cycle_type_id', 'name', 'price', 'bought_at', 'finished_at'])]
class CycleEntry extends Model
{
    /** @use HasFactory<CycleEntryFactory> */
    use BelongsToOwnerThrough, HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{price: 'integer'}
     */
    protected function casts(): array
    {
        return ['price' => 'integer'];
    }

    /**
     * @return BelongsTo<CycleType, $this>
     */
    public function cycleType(): BelongsTo
    {
        return $this->belongsTo(CycleType::class);
    }

    /**
     * @return array{0: class-string<Model>, 1: string}
     */
    protected static function ownerParent(): array
    {
        return [CycleType::class, 'cycle_type_id'];
    }
}
