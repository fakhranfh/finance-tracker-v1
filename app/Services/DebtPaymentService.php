<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Record;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Pays debts off with records: a debt is repaid with expense records, a receivable settled with income records.
 * The paid amount of a debt or sub debt is the sum of the records linked to it.
 */
class DebtPaymentService
{
    public function __construct(
        protected RecordRepositoryInterface $recordRepository,
        protected RecordManagementService $recordService,
    ) {}

    /**
     * The records that pay the debt off, or one of its sub debts when `$subDebtId` is given.
     *
     * @return LengthAwarePaginator<int, Record>
     */
    public function paginate(Debt $debt, ?int $subDebtId = null, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->recordRepository->paginateForDebt($debt->id, $subDebtId, $perPage, $search, $sort, $direction);
    }

    /**
     * Existing records that could pay the debt off: of the matching type and not linked to a debt yet.
     *
     * @return LengthAwarePaginator<int, Record>
     */
    public function candidates(Debt $debt, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->recordRepository->paginateAvailable($debt->type->recordType()->value, $perPage, $search, $sort, $direction);
    }

    /**
     * Pay the debt off with an existing record (`record_id`) or with a new one made right here.
     *
     * @param  array<string, mixed>  $data
     */
    public function add(Debt $debt, array $data): Record
    {
        $subDebtId = isset($data['sub_debt_id']) ? (int) $data['sub_debt_id'] : null;

        if (! empty($data['record_id'])) {
            $record = $this->recordRepository->find((int) $data['record_id']);
            $this->recordRepository->linkToDebt($record, $debt->id, $subDebtId);

            return $this->recordRepository->find($record->id);
        }

        return $this->recordService->create([
            'type' => $debt->type->recordType()->value,
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'recorded_at' => $data['recorded_at'],
            'debt_id' => $debt->id,
            'sub_debt_id' => $subDebtId,
        ]);
    }

    /**
     * Stop the record from paying the debt off; the record stays.
     */
    public function remove(Record $record): void
    {
        $this->recordRepository->unlinkFromDebt($record);
    }
}
