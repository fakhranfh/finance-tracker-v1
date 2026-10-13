<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Exports\RecordsExport;
use App\Models\Record;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Record\RecordRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordManagementService
{
    private const EXPORT_DATE_FORMAT = 'd M Y, H:i';

    public function __construct(
        protected RecordRepositoryInterface $recordRepository,
        protected AccountRepositoryInterface $accountRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected AccountLastBalanceService $lastBalance,
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountMonthlyTotalsService $monthlyTotals,
        protected AccountYearlyTotalsService $yearlyTotals,
        protected AccountSummaryService $accountSummaries,
    ) {}

    /**
     * List records, paginated, optionally filtered and sorted.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        return $this->recordRepository->paginate($perPage, $search, $sort, $direction, $filters);
    }

    /**
     * Download every record matching the search and filters, in table order, as xlsx or pdf.
     *
     * @param  array<string, mixed>  $filters
     */
    public function export(string $format, string $timezone, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): BinaryFileResponse|Response|StreamedResponse
    {
        $records = $this->recordRepository->export($search, $sort, $direction, $filters);
        $headings = [
            __('records.table.recorded_at'),
            __('records.table.type'),
            __('records.table.category'),
            __('records.fields.from_account'),
            __('records.fields.to_account'),
            __('records.table.amount'),
            __('records.table.note'),
        ];
        $filterLines = $this->filterLines($search, $filters);
        $filename = 'records-'.now()->format('Ymd-His').'.'.$format;

        return $format === 'pdf'
            ? Pdf::loadView('records.export', ['records' => $records->all(), 'headings' => $headings, 'filterLines' => $filterLines, 'timezone' => $timezone, 'dateFormat' => self::EXPORT_DATE_FORMAT])
                ->setPaper('a4', 'landscape')
                ->download($filename)
            : Excel::download(new RecordsExport($records, $headings, $timezone, self::EXPORT_DATE_FORMAT, $filterLines), $filename);
    }

    /**
     * Describe the active search and filters as "Label: value" lines for the export header.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function filterLines(?string $search, array $filters): array
    {
        $money = fn (string $v): string => 'Rp '.number_format((float) $v, 2, ',', '.');
        $lines = [];

        if ($search) {
            $lines[__('records.filters.search_label')] = $search;
        }
        if (! empty($filters['type'])) {
            $lines[__('records.filters.type')] = collect($filters['type'])
                ->map(fn (string $t) => RecordType::from($t)->label())->implode(', ');
        }
        if (! empty($filters['account_ids'])) {
            $lines[__('records.filters.account')] = $this->accountRepository->namesOf($filters['account_ids'])->implode(', ');
        }
        if (! empty($filters['category_ids'])) {
            $names = $this->categoryRepository->namesOf(array_filter($filters['category_ids'], 'is_numeric'));

            if (in_array('none', $filters['category_ids'], true)) {
                $names->push(__('records.fields.no_category'));
            }

            $lines[__('records.filters.category')] = $names->implode(', ');
        }
        if (! empty($filters['date_from'])) {
            $lines[__('records.filters.date_from')] = $filters['date_from'];
        }
        if (! empty($filters['date_to'])) {
            $lines[__('records.filters.date_to')] = $filters['date_to'];
        }
        if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
            $lines[__('records.filters.amount_min')] = $money((string) $filters['amount_min']);
        }
        if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
            $lines[__('records.filters.amount_max')] = $money((string) $filters['amount_max']);
        }

        return collect($lines)->map(fn (string $v, string $k) => "$k: $v")->values()->all();
    }

    /**
     * Summarize the records matching the search and filters like an account summary: income,
     * expense, transfers in and out, and net. When only accounts and a date range filter the
     * records, the totals come from the stored account totals; any other filter needs the
     * records themselves.
     *
     * @param  array<string, mixed>  $filters
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}
     */
    public function summarize(?string $search, array $filters, string $timezone): array
    {
        $count = $this->recordRepository->countMatching($search, filters: $filters);
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;
        $storedOnly = ! $search
            && empty($filters['type']) && empty($filters['category_ids'])
            && ($filters['amount_min'] ?? '') === '' && ($filters['amount_max'] ?? '') === ''
            && (($from && $to) || (! $from && ! $to));

        if ($storedOnly) {
            $accountIds = ! empty($filters['account_ids']) ? $filters['account_ids'] : $this->accountRepository->allIds();
            $flows = $this->accountSummaries->flowsOfAccounts(
                $accountIds,
                $from ? CarbonImmutable::parse($from, $timezone)->startOfDay() : null,
                $to ? CarbonImmutable::parse($to, $timezone)->endOfDay() : null,
            );

            return [...$flows, 'record_count' => $count];
        }

        $flows = $this->recordRepository->flows($search, $filters);
        $net = round($flows['income'] - $flows['expense'] + $flows['transfer_in'] - $flows['transfer_out'], 2);

        return [...$flows, 'net' => $net];
    }

    /**
     * Find a single record by ID.
     */
    public function find(int $id): ?Record
    {
        return $this->recordRepository->find($id);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Record
    {
        return $this->recordRepository->create($this->normalize($data));
    }

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Record $record, array $data): Record
    {
        return $this->recordRepository->update($record, $this->normalize($data));
    }

    /**
     * Delete a record.
     */
    public function delete(Record $record): void
    {
        $this->recordRepository->delete($record);
    }

    /**
     * Delete multiple records by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->applyDeletion($this->recordRepository->deleteMany($ids));
    }

    /**
     * Delete every record matching the search and filters (all when none apply).
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteMatching(?string $search = null, array $filters = []): void
    {
        $this->applyDeletion($this->recordRepository->deleteMatching($search, $filters));
    }

    /**
     * Count records matching the search and filters, optionally excluding one ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        return $this->recordRepository->countMatching($search, $exceptId, $filters);
    }

    /**
     * Only transfers have a destination account, and transfers have no category (validation allows none).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if ($data['type'] !== RecordType::Transfer->value) {
            $data['to_account_id'] = null;
        }

        return $data;
    }

    /**
     * Bring balances, snapshots and monthly and yearly totals in line with records removed in bulk.
     *
     * @param  array{account_ids: array<int, int|null>, first_id: ?int, effects: Collection<int, \stdClass>}  $deletion
     */
    private function applyDeletion(array $deletion): void
    {
        foreach ($deletion['effects'] as $effect) {
            $this->lastBalance->apply($effect->type, $effect->account_id, $effect->to_account_id, $effect->total, -1);
        }

        if ($deletion['first_id'] !== null) {
            $this->snapshots->invalidate($deletion['account_ids'], $deletion['first_id']);
        }

        $this->monthlyTotals->refresh($deletion['account_ids']);
        $this->yearlyTotals->refresh($deletion['account_ids']);
    }
}
