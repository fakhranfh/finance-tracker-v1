<?php

namespace App\Jobs;

use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use App\Services\AccountMonthlyTotalsService;
use App\Services\AccountSummaryService;
use App\Services\AccountYearlyTotalsService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Apply the side effects of record changes to one account in the background:
 * monthly and yearly totals, last balance and balance snapshots. The opening
 * balance is read before the totals are rebuilt, because it is last_balance
 * minus the net of the totals as they were before the change.
 *
 * Every rebuild reads all records of the account, so changes are merged: request()
 * keeps what is pending per account in the cache and queues one job only while
 * nothing is pending. The job takes everything that piled up and handles it in
 * one rebuild.
 */
class SyncRecordEffects implements ShouldQueue
{
    use Queueable;

    private const PENDING_TTL_MINUTES = 60;

    public function __construct(public int $accountId) {}

    /**
     * Records that the accounts need syncing. Runs after the transaction commits.
     *
     * @param  array<int, int|null>  $accountIds
     * @param  int|null  $invalidateFromRecordId  Snapshots from this record ID on are no longer valid.
     * @param  int|null  $createdRecordId  A new record that gets its own snapshot.
     */
    public static function request(array $accountIds, ?int $invalidateFromRecordId = null, ?int $createdRecordId = null, bool $backfill = false): void
    {
        $ids = array_values(array_unique(array_filter($accountIds)));

        DB::afterCommit(function () use ($ids, $invalidateFromRecordId, $createdRecordId, $backfill): void {
            foreach ($ids as $id) {
                $isFirst = self::withLock($id, function () use ($id, $invalidateFromRecordId, $createdRecordId, $backfill): bool {
                    $pending = Cache::get(self::key($id));

                    Cache::put(
                        self::key($id),
                        self::merge($pending, $invalidateFromRecordId, $createdRecordId !== null ? [$createdRecordId] : [], $backfill),
                        now()->addMinutes(self::PENDING_TTL_MINUTES),
                    );

                    return $pending === null;
                });

                // Outside the lock: a sync queue runs the job right here, and the job takes the same lock.
                if ($isFirst) {
                    self::dispatch($id);
                }
            }
        });
    }

    public function handle(
        AccountBalanceRepositoryInterface $balances,
        AccountSummaryService $summaries,
        AccountMonthlyTotalsService $monthlyTotals,
        AccountYearlyTotalsService $yearlyTotals,
    ): void {
        $pending = self::withLock($this->accountId, fn (): ?array => Cache::pull(self::key($this->accountId)));

        if ($pending === null) {
            return;
        }

        try {
            $this->sync($pending, $balances, $summaries, $monthlyTotals, $yearlyTotals);
        } catch (Throwable $e) {
            // Put the changes back so the retry still knows about them.
            self::withLock($this->accountId, fn () => Cache::put(
                self::key($this->accountId),
                self::merge(Cache::get(self::key($this->accountId)), $pending['invalidate_from'], $pending['created'], $pending['backfill']),
                now()->addMinutes(self::PENDING_TTL_MINUTES),
            ));

            throw $e;
        }
    }

    /**
     * @param  array{invalidate_from: int|null, created: array<int, int>, backfill: bool}  $pending
     */
    private function sync(
        array $pending,
        AccountBalanceRepositoryInterface $balances,
        AccountSummaryService $summaries,
        AccountMonthlyTotalsService $monthlyTotals,
        AccountYearlyTotalsService $yearlyTotals,
    ): void {
        $id = $this->accountId;
        $ids = [$id];

        $openings = [];
        if (! $pending['backfill']) {
            $openings[$id] = round((float) $balances->lastBalance($id) - $summaries->allTime($id, false)['net'], 2);
        }

        $monthlyTotals->refresh($ids, $openings);
        $yearlyTotals->refresh($ids);

        if ($pending['invalidate_from'] !== null) {
            $balances->deleteSnapshots($ids, $pending['invalidate_from']);
        }

        $totals = $summaries->allTime($id, false);
        // A backfill changes no record, so last_balance already includes all of them.
        $opening = $openings[$id] ??= round((float) $balances->lastBalance($id) - $totals['net'], 2);
        $balance = number_format($opening + $totals['net'], 2, '.', '');

        $balances->setLastBalance($id, $balance);

        // One snapshot per new record, even when several arrive before this job runs (minus any deleted since).
        $created = $pending['created'] === [] ? [] : DB::table('records')->whereIn('id', $pending['created'])->orderBy('id')->pluck('id')->all();

        foreach ($created as $createdRecordId) {
            $base = $balances->latestSnapshot($id, $createdRecordId);
            $flows = $balances->flowsAfterRecords(
                $id,
                $base['flows'] ?? ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'net' => 0, 'record_count' => 0],
                $base['last_record_id'] ?? 0,
                $createdRecordId,
            );
            $balances->storeSnapshot($id, $createdRecordId, number_format($opening + (float) $flows['net'], 2, '.', ''), $flows);
        }

        // Without new records, or when a change dropped snapshots, take one at the newest record.
        if ($created === [] || $pending['invalidate_from'] !== null) {
            $lastRecordId = $balances->latestRecordId($id);

            if ($lastRecordId !== null) {
                $balances->storeSnapshot($id, $lastRecordId, $balance, $totals);
            }
        }
    }

    /**
     * @param  array{invalidate_from: int|null, created: array<int, int>, backfill: bool}|null  $pending
     * @param  array<int, int>  $created
     * @return array{invalidate_from: int|null, created: array<int, int>, backfill: bool}
     */
    private static function merge(?array $pending, ?int $invalidateFrom, array $created, bool $backfill): array
    {
        $invalidate = array_filter([$pending['invalidate_from'] ?? null, $invalidateFrom], fn ($value) => $value !== null);
        $createdIds = array_unique([...($pending['created'] ?? []), ...$created]);
        sort($createdIds);

        return [
            'invalidate_from' => $invalidate === [] ? null : min($invalidate),
            'created' => $createdIds,
            // A backfill has no known opening balance to protect, so any real change makes it a normal sync.
            'backfill' => $pending === null ? $backfill : $pending['backfill'] && $backfill,
        ];
    }

    private static function key(int $accountId): string
    {
        return "sync-record-effects:pending:{$accountId}";
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private static function withLock(int $accountId, callable $callback): mixed
    {
        try {
            return Cache::lock("sync-record-effects:lock:{$accountId}", 10)->block(5, $callback);
        } catch (LockTimeoutException) {
            // Better to risk a lost merge than to fail the request or the job.
            return $callback();
        }
    }
}
