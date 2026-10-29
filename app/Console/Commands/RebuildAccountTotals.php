<?php

namespace App\Console\Commands;

use App\Jobs\SyncRecordEffects;
use App\Models\Account;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * The monthly and yearly totals are bucketed in their owner's timezone. A login
 * that changes it already rebuilds that user's; this forces a rebuild for every account.
 * Balances and records are not touched.
 */
#[AsCommand(name: 'accounts:rebuild-totals')]
class RebuildAccountTotals extends Command
{
    protected $signature = 'accounts:rebuild-totals';

    protected $description = 'Queue a rebuild of the monthly and yearly totals of every account.';

    public function handle(): int
    {
        $ids = Account::query()->pluck('id')->all();

        SyncRecordEffects::request($ids, null, null, true);

        $this->info('Queued a rebuild for '.count($ids).' account(s).');

        return self::SUCCESS;
    }
}
