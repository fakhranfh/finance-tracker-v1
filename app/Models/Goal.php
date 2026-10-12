<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something the user is saving up for, with a target amount and a desired date.
 */
#[Fillable(['name', 'target_amount', 'saved_amount', 'desired_date', 'color', 'icon', 'status', 'note'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{target_amount: 'decimal:2', saved_amount: 'decimal:2', status: 'App\Enums\GoalStatus'}
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'saved_amount' => 'decimal:2',
            'status' => GoalStatus::class,
        ];
    }

    /**
     * @return HasMany<GoalSaving, $this>
     */
    public function savings(): HasMany
    {
        return $this->hasMany(GoalSaving::class);
    }
}
