<?php

namespace App\Observers;

use App\Jobs\SyncRecordEffects;
use App\Models\Record;

class RecordObserver
{
    public function created(Record $record): void
    {
        SyncRecordEffects::dispatch([$record->account_id, $record->to_account_id], null, $record->id)->afterCommit();
    }

    public function updated(Record $record): void
    {
        SyncRecordEffects::dispatch(
            [
                $record->account_id,
                $record->to_account_id,
                $record->getOriginal('account_id'),
                $record->getOriginal('to_account_id'),
            ],
            $record->id,
        )->afterCommit();
    }

    public function deleted(Record $record): void
    {
        SyncRecordEffects::dispatch(
            [$record->account_id, $record->to_account_id],
            $record->id,
        )->afterCommit();
    }
}
