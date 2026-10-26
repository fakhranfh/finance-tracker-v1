<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Database\Factories\SubDebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One instalment or part of a debt or receivable, e.g. a single monthly payment.
 */
#[Fillable(['debt_id', 'name', 'amount', 'paid_amount', 'due_date', 'is_paid', 'note'])]
class SubDebt extends Model
{
    /** @use HasFactory<SubDebtFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_amount' => 'integer',
            'is_paid' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
