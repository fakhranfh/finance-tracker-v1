<?php

namespace App\Models;

use App\Enums\DebtType;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money the user owes (debt) or is owed (receivable). What has been paid is the sum of the records
 * linked to it as payments, never stored. Forgiving it writes the rest off (`forgiven_at`).
 */
#[Fillable(['type', 'account_id', 'name', 'amount', 'due_date', 'notes'])]
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{type: 'App\Enums\DebtType', amount: 'integer', forgiven_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'type' => DebtType::class,
            'amount' => 'integer',
            'forgiven_at' => 'datetime',
        ];
    }

    /**
     * The sum of the linked records; read from the `paid_amount` the repository selected when it is there.
     *
     * @return Attribute<int, never>
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): int => array_key_exists('paid_amount', $attributes)
            ? (int) $value
            : (int) $this->payments()->sum('amount'));
    }

    /**
     * Whether the rest of the debt was written off instead of paid.
     *
     * @return Attribute<bool, never>
     */
    protected function isForgiven(): Attribute
    {
        return Attribute::get(fn (): bool => $this->forgiven_at !== null);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isPaid(): Attribute
    {
        return Attribute::get(fn (): bool => $this->is_forgiven || $this->paid_amount >= $this->amount);
    }

    /**
     * @return Attribute<int<0, max>, never>
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn (): int => $this->is_forgiven ? 0 : max(0, $this->amount - $this->paid_amount));
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * The records that pay this debt off.
     *
     * @return HasMany<Record, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * @return HasMany<SubDebt, $this>
     */
    public function subDebts(): HasMany
    {
        return $this->hasMany(SubDebt::class);
    }
}
