<?php

namespace App\Models;

use App\Enums\RecordType;
use App\Observers\RecordObserver;
use Database\Factories\RecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An income, expense or transfer. For transfers, `account_id` is the source
 * and `to_account_id` the destination.
 * */
#[Fillable(['type', 'account_id', 'to_account_id', 'category_id', 'amount', 'note', 'recorded_at'])]
#[ObservedBy(RecordObserver::class)]
class Record extends Model
{
    /** @use HasFactory<RecordFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array{type: 'App\Enums\RecordType', amount: 'decimal:2', recorded_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'type' => RecordType::class,
            'amount' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
