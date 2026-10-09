<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Jobs\GenerateAccountRecords;
use App\Models\Account;
use App\Models\Category;
use App\Models\Record;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Dev-only: creates sample records on one account, dated inside a period so
 * they show up in that period's summary and record list.
 */
class AccountSampleRecordService
{
    /**
     * Queue a background job that runs generate() with the same arguments.
     */
    public function queue(
        Account $account,
        int $count,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?RecordType $type = null,
        string|int|float|null $amount = null,
    ): void {
        GenerateAccountRecords::dispatch($account->id, $count, $start, $end, $type, $amount);
    }

    /**
     * Delete every record that touches $account, one by one so the record
     * observer still syncs balances and totals.
     */
    public function deleteAll(Account $account): void
    {
        Record::query()
            ->where('account_id', $account->id)
            ->orWhere('to_account_id', $account->id)
            ->chunkById(200, fn (Collection $records) => $records->each->delete());
    }

    /**
     * Create exactly $count records on $account, with recorded_at between $start and $end.
     * A null $type mixes all types; a null $amount picks a random one per record.
     * Transfers go to another account; one is created when none exists.
     *
     * @return Collection<int, Record>
     */
    public function generate(
        Account $account,
        int $count,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?RecordType $type = null,
        string|int|float|null $amount = null,
    ): Collection {
        $cases = RecordType::cases();

        $types = collect(range(0, $count - 1))
            ->map(fn (int $i) => $type ?? $cases[$i % count($cases)])
            ->shuffle();

        $counterpartIds = Account::query()->whereKeyNot($account->id)->pluck('id')->all();

        if ($counterpartIds === [] && $types->contains(RecordType::Transfer)) {
            $counterpartIds = [Account::factory()->create()->id];
        }

        return $types->map(function (RecordType $type) use ($account, $start, $end, $counterpartIds, $amount) {
            $attributes = [
                'type' => $type,
                'account_id' => $account->id,
                'category_id' => Category::randomIdOfType($type),
                'recorded_at' => fake()->dateTimeBetween($start, $end),
            ];

            if ($amount !== null) {
                $attributes['amount'] = $amount;
            }

            if ($type === RecordType::Transfer) {
                $attributes['to_account_id'] = fake()->randomElement($counterpartIds);
            }

            return Record::factory()->state($attributes)->create();
        });
    }
}
