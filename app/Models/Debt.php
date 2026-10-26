<?php

namespace App\Models;

use App\Enums\DebtType;
use App\Models\Concerns\HasUserTimezone;
use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid'])]
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DebtType::class,
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }
}
