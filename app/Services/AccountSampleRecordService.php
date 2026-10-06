<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Account;
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
     * Create exactly $count records on $account, with recorded_at between $start and $end.
     * Transfers go to another account; one is created when none exists.
     *
     * @return Collection<int, Record>
     */
    public function generate(Account $account, int $count, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $cases = RecordType::cases();

        $types = collect(range(0, $count - 1))
            ->map(fn (int $i) => $cases[$i % count($cases)])
            ->shuffle();

        $counterpartIds = Account::query()->whereKeyNot($account->id)->pluck('id')->all();

        if ($counterpartIds === [] && $types->contains(RecordType::Transfer)) {
            $counterpartIds = [Account::factory()->create()->id];
        }

        return $types->map(function (RecordType $type) use ($account, $start, $end, $counterpartIds) {
            $attributes = [
                'type' => $type,
                'account_id' => $account->id,
                'recorded_at' => fake()->dateTimeBetween($start, $end),
            ];

            if ($type === RecordType::Transfer) {
                $attributes['to_account_id'] = fake()->randomElement($counterpartIds);
            }

            return Record::factory()->state($attributes)->create();
        });
    }
}
