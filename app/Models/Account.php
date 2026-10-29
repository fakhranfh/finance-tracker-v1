<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string|null $balance Only present when loaded through the withBalance() scope.
 */
#[Fillable(['name', 'last_balance', 'color', 'icon', 'sort_order'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use BelongsToUser, HasFactory, HasUserTimezone;

    /**
     * Add a `balance` column: the account's current balance, read from the
     * `last_balance` column that is kept current as records change.
     *
     * @param  Builder<Account>  $query
     */
    #[Scope]
    protected function withBalance(Builder $query): void
    {
        $query->selectRaw('accounts.*, accounts.last_balance as balance');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_balance' => 'integer',
            'balance' => 'integer',
        ];
    }

    /**
     * Records of this account (deleted together with it).
     *
     * @return HasMany<Record, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
