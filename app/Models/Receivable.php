<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Database\Factories\ReceivableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid'])]
class Receivable extends Model
{
    /** @use HasFactory<ReceivableFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }
}
