<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property int|null $user_id
 *
 * Keeps the model's rows private to their owner: while someone is signed in every query is limited to
 * their rows and new rows are created for them. Without a signed-in user (queue workers, the console,
 * the webhook endpoint before it signs in as the webhook owner) nothing is limited.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query): void {
            if (($userId = Auth::id()) !== null) {
                $query->where($query->getModel()->getTable().'.user_id', $userId);
            }
        });

        static::creating(function (self $model): void {
            $model->user_id ??= Auth::id();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
