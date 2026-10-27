<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Database\Factories\SubDebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One instalment or part of a debt or receivable, e.g. a single monthly payment. What has been paid is the
 * sum of the records linked to it, never stored. Forgiving its debt writes the rest off (`forgiven_at`).
 */
#[Fillable(['debt_id', 'name', 'amount', 'due_date', 'note'])]
class SubDebt extends Model
{
    /** @use HasFactory<SubDebtFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{amount: 'integer', forgiven_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
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
     * @return Attribute<bool, never>
     */
    protected function isPaid(): Attribute
    {
        return Attribute::get(fn (): bool => $this->forgiven_at !== null || $this->paid_amount >= $this->amount);
    }

    /**
     * @return Attribute<int<0, max>, never>
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn (): int => $this->forgiven_at !== null ? 0 : max(0, $this->amount - $this->paid_amount));
    }

    /**
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * The records that pay this sub debt off.
     *
     * @return HasMany<Record, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
