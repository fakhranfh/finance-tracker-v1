<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebtPayment\StoreDebtPaymentRequest;
use App\Http\Requests\DebtPayment\UpdateDebtPaymentRequest;
use App\Http\Resources\DebtPaymentResource;
use App\Http\Resources\DebtPaymentRowResource;
use App\Http\Resources\UnrecordedPaymentResource;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Services\DebtPaymentService;
use App\Traits\DebtPayment\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Payments of a debt or receivable: the records linked to it (or to one of its sub debts) and the payments
 * saved without a record.
 */
class DebtPaymentController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected DebtPaymentService $debtPaymentService,
    ) {}

    public function index(Request $request, Debt $debt): AnonymousResourceCollection
    {
        return DebtPaymentRowResource::collection($this->debtPaymentService->paginate(
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
        $payment = $this->debtPaymentService->add($debt, $request->validated());
        $resource = $payment instanceof DebtPayment ? new UnrecordedPaymentResource($payment) : new DebtPaymentResource($payment);

        return $resource->response()->setStatusCode(201);
    }

    public function show(Debt $debt, int $payment): DebtPaymentResource|UnrecordedPaymentResource
    {
        $found = $this->debtPaymentService->find($debt, $payment);
        abort_if($found === null, 404);

        return $found instanceof DebtPayment ? new UnrecordedPaymentResource($found) : new DebtPaymentResource($found);
    }

    public function update(UpdateDebtPaymentRequest $request, Debt $debt, int $payment): DebtPaymentResource|UnrecordedPaymentResource
    {
        $updated = $this->debtPaymentService->update($debt, $payment, $request->validated());
        abort_if($updated === null, 404);

        return $updated instanceof DebtPayment ? new UnrecordedPaymentResource($updated) : new DebtPaymentResource($updated);
    }

    public function destroy(Debt $debt, int $payment): Response
    {
        abort_unless($this->debtPaymentService->remove($debt, $payment), 404);

        return response()->noContent();
    }
}
