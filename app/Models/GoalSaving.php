<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One amount added to a goal's saved amount, kept as history.
 */
#[Fillable(['goal_id', 'sub_goal_id', 'amount'])]
class GoalSaving extends Model
{
    use HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{amount: 'decimal:2'}
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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
     * @return BelongsTo<SubGoal, $this>
     */
    public function subGoal(): BelongsTo
    {
        return $this->belongsTo(SubGoal::class);
    }
}
