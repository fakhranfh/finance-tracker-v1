<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebtPayment\StoreDebtPaymentRequest;
use App\Http\Resources\DebtPaymentResource;
use App\Models\Debt;
use App\Models\Record;
use App\Services\DebtPaymentService;
use App\Traits\DebtPayment\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Payments of a debt or receivable: the records linked to it (or to one of its sub debts).
 */
class DebtPaymentController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected DebtPaymentService $debtPaymentService,
    ) {}

    public function index(Request $request, Debt $debt): AnonymousResourceCollection
    {
        return DebtPaymentResource::collection($this->debtPaymentService->paginate(
            $debt,
            $request->integer('sub_debt_id') ?: null,
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $request->string('search')->value() ?: null,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'desc')->value(),
        ))->additional([
            'summary' => [
                'amount' => $debt->amount,
                'paid' => $debt->paid_amount,
                'remaining' => $debt->remaining_amount,
            ],
        ]);
    }

    public function available(Request $request, Debt $debt): AnonymousResourceCollection
    {
        return DebtPaymentResource::collection($this->debtPaymentService->candidates(
            $debt,
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $request->string('search')->value() ?: null,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'desc')->value(),
        ));
    }

    public function store(StoreDebtPaymentRequest $request, Debt $debt): JsonResponse
    {
        $record = $this->debtPaymentService->add($debt, $request->validated());

        return (new DebtPaymentResource($record))->response()->setStatusCode(201);
    }

    public function destroy(Debt $debt, Record $record): Response
    {
        abort_unless($record->debt_id === $debt->id, 404);

        $this->debtPaymentService->remove($record);

        return response()->noContent();
    }
}
