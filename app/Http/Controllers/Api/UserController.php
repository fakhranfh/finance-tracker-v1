<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\BulkDeleteUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use App\Traits\User\DocumentsApi;
use App\Traits\User\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected UserManagementService $userManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return UserResource::collection($this->userManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->userManagementService->countMatching($search, $request->user()->id),
        ]);
    }

    public function bulkDestroy(BulkDeleteUserRequest $request): Response
    {
        // Nobody deletes themselves, so an admin always remains.
        $self = $request->user()->id;

        if ($request->boolean('all')) {
            $this->userManagementService->deleteMatching($request->validated('search'), $self);
        } else {
            $this->userManagementService->deleteMany(array_values(array_diff($request->validated('ids'), [$self])));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return UserResource::collection($this->generateSampleUsers($validated['count']));
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userManagementService->create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        // An admin cannot demote themselves, which could leave nobody to manage users.
        if ($user->is($request->user())) {
            unset($data['role']);
        }

        return new UserResource($this->userManagementService->update($user, $data));
    }

    public function destroy(Request $request, User $user): Response
    {
        abort_if($user->is($request->user()), 403, __('users.errors.delete_self'));

        $this->userManagementService->delete($user);

        return response()->noContent();
    }
}
