<?php

namespace App\Observers;

use App\Models\Record;
use App\Services\AccountBalanceSnapshotService;
use App\Services\AccountLastBalanceService;

class RecordObserver
{
    public function __construct(
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountLastBalanceService $lastBalance,
    ) {}

    public function created(Record $record): void
    {
        $this->lastBalance->apply($record->type, $record->account_id, $record->to_account_id, $record->amount);
        $this->snapshots->refreshIfNeeded([$record->account_id, $record->to_account_id]);
    }

    public function updated(Record $record): void
    {
        if ($record->wasChanged(['type', 'account_id', 'to_account_id', 'amount'])) {
            $this->lastBalance->apply(
                $record->getOriginal('type'),
                $record->getOriginal('account_id'),
                $record->getOriginal('to_account_id'),
                $record->getOriginal('amount'),
                -1,
            );
            $this->lastBalance->apply($record->type, $record->account_id, $record->to_account_id, $record->amount);
        }

        $this->snapshots->invalidate([
            $record->account_id,
            $record->to_account_id,
            $record->getOriginal('account_id'),
            $record->getOriginal('to_account_id'),
        ], $record->id);
    }

    public function deleted(Record $record): void
    {
        $this->lastBalance->apply($record->type, $record->account_id, $record->to_account_id, $record->amount, -1);
        $this->snapshots->invalidate([$record->account_id, $record->to_account_id], $record->id);
    }
}
