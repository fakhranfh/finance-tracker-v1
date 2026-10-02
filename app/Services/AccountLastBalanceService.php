<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;

/**
 * Keeps `accounts.last_balance` current as records change, without
 * waiting for a snapshot.
 */
class AccountLastBalanceService
{
    public function __construct(protected AccountBalanceRepositoryInterface $balances) {}

    /**
     * Apply (sign 1) or undo (sign -1) the effect of a record on its accounts.
     */
    public function apply(RecordType|string $type, ?int $accountId, ?int $toAccountId, string|float $amount, int $sign = 1): void
    {
        $type = $type instanceof RecordType ? $type : RecordType::from($type);

        if ($type === RecordType::Transfer) {
            $this->adjust($accountId, $amount, -$sign);
            $this->adjust($toAccountId, $amount, $sign);

            return;
        }

        $this->adjust($accountId, $amount, $type === RecordType::Income ? $sign : -$sign);
    }

    /**
     * Add a signed amount to an account's last balance.
     */
    public function adjust(?int $accountId, string|float $amount, int $sign = 1): void
    {
        if (! $accountId) {
            return;
        }

        $this->balances->adjustLastBalance($accountId, ($sign < 0 ? '-' : '').$amount);
    }
}
