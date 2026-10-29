<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwnerThrough;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\SubGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing that has to be achieved to reach a goal, e.g. the dowry or a vendor for a wedding goal.
 */
#[Fillable(['goal_id', 'name', 'target_amount', 'saved_amount', 'saved_amount_before_done', 'desired_date', 'is_done', 'note'])]
class SubGoal extends Model
{
    /** @use HasFactory<SubGoalFactory> */
    use BelongsToOwnerThrough, HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'saved_amount' => 'integer',
            'saved_amount_before_done' => 'integer',
            'is_done' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return array{0: class-string<Model>, 1: string}
     */
    protected static function ownerParent(): array
    {
        return [Goal::class, 'goal_id'];
    }
}
