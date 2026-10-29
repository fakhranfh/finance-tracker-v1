<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwnerThrough;
use App\Models\Concerns\HasUserTimezone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One amount added to a goal's saved amount, kept as history.
 */
#[Fillable(['goal_id', 'sub_goal_id', 'type', 'amount'])]
class GoalSaving extends Model
{
    use BelongsToOwnerThrough, HasUserTimezone;

    /** A history entry that adds an amount to the saved amount. */
    public const TYPE_SAVING = 'saving';

    /** A history entry (amount 0) noting that its sub goal was marked as done. */
    public const TYPE_DONE = 'done';

    /**
     * Get the attributes that should be cast.
     *
     * @return array{amount: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
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

    /**
     * @return array{0: class-string<Model>, 1: string}
     */
    protected static function ownerParent(): array
    {
        return [Goal::class, 'goal_id'];
    }
}
