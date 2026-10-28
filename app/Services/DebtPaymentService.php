<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Record;
use App\Repositories\DebtPayment\DebtPaymentRepositoryInterface;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Pays debts off with records: a debt is repaid with expense records, a receivable settled with income records.
 * A payment can also be saved without a record, which moves no money in or out of an account. The paid amount of
 * a debt or sub debt is the sum of both, never stored.
 */
class DebtPaymentService
{
    public function __construct(
        protected RecordRepositoryInterface $recordRepository,
        protected DebtPaymentRepositoryInterface $paymentRepository,
        protected RecordManagementService $recordService,
    ) {}

    /**
     * Every payment of the debt, or one of its sub debts when `$subDebtId` is given: the records and the payments
     * saved without a record.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    public function paginate(Debt $debt, ?int $subDebtId = null, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->paymentRepository->paginateForDebt($debt->id, $subDebtId, $perPage, $search, $sort, $direction);
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
     * Pay the debt off with an existing record (`record_id`), with a new one made right here, or, when
     * `create_record` is false, with a payment that is not saved as a record.
     *
     * @param  array<string, mixed>  $data
     */
    public function add(Debt $debt, array $data): Record|DebtPayment
    {
        $subDebtId = isset($data['sub_debt_id']) ? (int) $data['sub_debt_id'] : null;

        if (! empty($data['record_id'])) {
            $record = $this->recordRepository->find((int) $data['record_id']);
            $this->recordRepository->linkToDebt($record, $debt->id, $subDebtId);

            return $this->recordRepository->find($record->id);
        }

        if (! ($data['create_record'] ?? true)) {
            return $this->paymentRepository->create([
                'debt_id' => $debt->id,
                'sub_debt_id' => $subDebtId,
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'paid_at' => $data['recorded_at'],
            ]);
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
     * Find a payment of the debt. `$key` is a record id, or the negated id of a payment saved without a record, as listed.
     */
    public function find(Debt $debt, int $key): Record|DebtPayment|null
    {
        if ($key < 0) {
            return $this->paymentRepository->findForDebt($debt->id, -$key);
        }

        $record = $this->recordRepository->find($key);

        return $record !== null && $record->debt_id === $debt->id ? $record : null;
    }

    /**
     * Change a payment: a record keeps paying the debt and its account balance follows, a payment saved without a
     * record just changes. Null when the debt has no such payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Debt $debt, int $key, array $data): Record|DebtPayment|null
    {
        $payment = $this->find($debt, $key);

        if ($payment === null) {
            return null;
        }

        if ($payment instanceof Record) {
            return $this->recordService->update($payment, [
                'type' => $payment->type->value,
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'] ?? null,
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'recorded_at' => $data['recorded_at'],
                'debt_id' => $debt->id,
                'sub_debt_id' => isset($data['sub_debt_id']) ? (int) $data['sub_debt_id'] : null,
            ]);
        }

        return $this->paymentRepository->update($payment, [
            'sub_debt_id' => isset($data['sub_debt_id']) ? (int) $data['sub_debt_id'] : null,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'paid_at' => $data['recorded_at'],
        ]);
    }

    /**
     * Take a payment off the debt. `$key` is a record id, or the negated id of a payment saved without a record.
     * A record stays; a payment without a record is deleted. False when the debt has no such payment.
     */
    public function remove(Debt $debt, int $key): bool
    {
        if ($key < 0) {
            $payment = $this->paymentRepository->findForDebt($debt->id, -$key);

            if ($payment === null) {
                return false;
            }

            $this->paymentRepository->delete($payment);

            return true;
        }

        $record = $this->recordRepository->find($key);

        if ($record === null || $record->debt_id !== $debt->id) {
            return false;
        }

        $this->recordRepository->unlinkFromDebt($record);

        return true;
    }
}
