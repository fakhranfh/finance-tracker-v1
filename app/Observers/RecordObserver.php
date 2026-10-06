<?php

namespace App\Observers;

use App\Jobs\SyncRecordEffects;
use App\Models\Record;

class RecordObserver
{
    public function created(Record $record): void
    {
        SyncRecordEffects::dispatch(
            [$record->account_id, $record->to_account_id],
            [$this->change($record->type, $record->account_id, $record->to_account_id, $record->amount, 1)],
            null,
            true,
        )->afterCommit();
    }

    public function updated(Record $record): void
    {
        $balanceChanges = [];

        if ($record->wasChanged(['type', 'account_id', 'to_account_id', 'amount'])) {
            $balanceChanges[] = $this->change(
                $record->getOriginal('type'),
                $record->getOriginal('account_id'),
                $record->getOriginal('to_account_id'),
                $record->getOriginal('amount'),
                -1,
            );
            $balanceChanges[] = $this->change($record->type, $record->account_id, $record->to_account_id, $record->amount, 1);
        }

        SyncRecordEffects::dispatch(
            [
                $record->account_id,
                $record->to_account_id,
                $record->getOriginal('account_id'),
                $record->getOriginal('to_account_id'),
            ],
            $balanceChanges,
            $record->id,
        )->afterCommit();
    }

    public function deleted(Record $record): void
    {
        SyncRecordEffects::dispatch(
            [$record->account_id, $record->to_account_id],
            [$this->change($record->type, $record->account_id, $record->to_account_id, $record->amount, -1)],
            $record->id,
        )->afterCommit();
    }

    /**
     * @return array{type: mixed, account_id: ?int, to_account_id: ?int, amount: string|float, sign: int}
     */
    private function change(mixed $type, ?int $accountId, ?int $toAccountId, string|float $amount, int $sign): array
    {
        return [
            'type' => $type,
            'account_id' => $accountId,
            'to_account_id' => $toAccountId,
            'amount' => $amount,
            'sign' => $sign,
        ];
    }
}
