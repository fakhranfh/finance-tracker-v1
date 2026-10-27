<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment on a debt or receivable that was not saved as a record: it counts towards what has been paid
 * but moves no money in or out of an account.
 */
#[Fillable(['debt_id', 'sub_debt_id', 'amount', 'note', 'paid_at'])]
class DebtPayment extends Model
{
    use HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{amount: 'integer', paid_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * @return BelongsTo<SubDebt, $this>
     */
    public function subDebt(): BelongsTo
    {
        return $this->belongsTo(SubDebt::class);
    }
}
