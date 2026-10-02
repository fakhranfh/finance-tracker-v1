<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\BulkDeleteAccountRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountManagementService;
use App\Traits\Account\DocumentsApi;
use App\Traits\Account\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected AccountManagementService $accountManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return AccountResource::collection($this->accountManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->accountManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteAccountRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->accountManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->accountManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return AccountResource::collection($this->generateSampleAccounts($validated['count']));
    }

    public function show(Account $account): AccountResource
    {
        return new AccountResource($this->accountManagementService->find($account->id));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->accountManagementService->create($request->validated());

        return (new AccountResource($this->accountManagementService->find($account->id)))->response()->setStatusCode(201);
    }

    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $updated = $this->accountManagementService->update($account, $request->validated());

        return new AccountResource($this->accountManagementService->find($updated->id));
    }

    public function destroy(Account $account): Response
    {
        $this->accountManagementService->delete($account);

        return response()->noContent();
    }
}
