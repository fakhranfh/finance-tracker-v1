<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Account;
use App\Services\AccountSampleRecordService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountRecordGenerateController extends Controller
{
    public function __construct(
        protected AccountSampleRecordService $accountSampleRecordService,
    ) {}

    public function __invoke(Request $request, Account $account): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $timezone = $request->user()?->timezone ?: config('app.timezone', 'UTC');
        [$start, $end] = $this->window($validated, $timezone);

        return RecordResource::collection(
            $this->accountSampleRecordService->generate($account, $validated['count'], $start, $end),
        );
    }

    /**
     * The period the records are dated in; the last 30 days when no period is given.
     *
     * @param  array{date_from?: ?string, date_to?: ?string}  $validated
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(array $validated, string $timezone): array
    {
        if (! empty($validated['date_from']) && ! empty($validated['date_to'])) {
            return [
                CarbonImmutable::createFromFormat('!Y-m-d', $validated['date_from'], $timezone)->startOfDay(),
                CarbonImmutable::createFromFormat('!Y-m-d', $validated['date_to'], $timezone)->endOfDay(),
            ];
        }

        $now = CarbonImmutable::now($timezone);

        return [$now->subDays(29)->startOfDay(), $now];
    }
}
