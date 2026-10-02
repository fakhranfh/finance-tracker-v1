<?php

namespace App\Models;

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
#[Fillable(['name', 'initial_balance'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Add a `balance` column: initial balance + income - expense
     * + transfers in - transfers out.
     *
     * @param  Builder<Account>  $query
     */
    #[Scope]
    protected function withBalance(Builder $query): void
    {
        $query->selectRaw(<<<'SQL'
            accounts.*,
            accounts.initial_balance
            + coalesce((select sum(case when r.type = 'income' then r.amount else -r.amount end)
                from records r where r.account_id = accounts.id), 0)
            + coalesce((select sum(r.amount)
                from records r where r.type = 'transfer' and r.to_account_id = accounts.id), 0) as balance
            SQL);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'balance' => 'decimal:2',
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
