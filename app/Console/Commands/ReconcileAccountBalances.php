<?php

namespace App\Console\Commands;

use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use App\Services\AccountBalanceSnapshotService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * `accounts.last_balance` is kept current incrementally, so a change that
 * bypasses the repositories and model events (raw SQL, seeders) leaves it
 * stale. This checks each balance against its newest snapshot plus the records
 * after it, and reports drift. Accounts without a snapshot are not checked.
 */
#[AsCommand(name: 'accounts:reconcile-balances')]
class ReconcileAccountBalances extends Command
{
    protected $signature = 'accounts:reconcile-balances
        {--fix : Overwrite drifted last balances and rebuild their snapshots}';

    protected $description = 'Compare each account\'s last balance with the balance computed from its records.';

    public function handle(AccountBalanceRepositoryInterface $balances, AccountBalanceSnapshotService $snapshots): int
    {
        $drifted = $balances->driftedAccounts();

        if ($drifted->isEmpty()) {
            $this->info('All account balances are in sync.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Last balance', 'Actual balance'],
            $drifted->map(fn ($row) => [$row->id, $row->name, $row->last_balance ?? '-', $row->actual_balance]),
        );

        if (! $this->option('fix')) {
            $this->warn("{$drifted->count()} account(s) out of sync. Re-run with --fix to correct them.");

            return self::FAILURE;
        }

        foreach ($drifted as $row) {
            $balances->setLastBalance($row->id, (string) $row->actual_balance);
        }

        $snapshots->invalidate($drifted->pluck('id')->all());

        $this->info("Fixed {$drifted->count()} account(s).");

        return self::SUCCESS;
    }
}
