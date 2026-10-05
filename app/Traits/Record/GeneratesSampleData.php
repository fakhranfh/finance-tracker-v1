<?php

namespace App\Traits\Record;

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the records list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample records backed by real database records.
     *
     * Record types are distributed evenly (income, expense, transfer) and shuffled.
     *
     * @param  array<int, int>  $accountIds  Accounts to spread the records across; empty creates new accounts.
     * @return Collection<int, Record>
     */
    protected function generateSampleRecords(int $count = 10, array $accountIds = []): Collection
    {
        $cases = RecordType::cases();

        $types = collect(range(0, $count - 1))
            ->map(fn (int $i) => $cases[$i % count($cases)])
            ->shuffle();

        return $types->map(function (RecordType $type) use ($accountIds) {
            $accountId = $accountIds === [] ? null : fake()->randomElement($accountIds);

            $attributes = ['type' => $type];

            if ($accountId !== null) {
                $attributes['account_id'] = $accountId;
            }

            if ($type === RecordType::Transfer) {
                $attributes['to_account_id'] = $this->sampleTransferTarget($accountIds, $accountId);
            }

            return Record::factory()->state($attributes)->create();
        });
    }

    /**
     * Pick a destination account for a sample transfer that differs from its source.
     *
     * @param  array<int, int>  $accountIds
     */
    private function sampleTransferTarget(array $accountIds, ?int $fromAccountId): int
    {
        $candidates = array_values(array_filter($accountIds, fn (int $id) => $id !== $fromAccountId));

        return $candidates === []
            ? Account::factory()->create()->id
            : fake()->randomElement($candidates);
    }
}
