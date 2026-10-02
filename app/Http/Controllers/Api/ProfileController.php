<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\UserManagementService;
use App\Traits\Profile\DocumentsApi;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected UserManagementService $userManagementService,
    ) {}

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        return new UserResource($this->userManagementService->update($request->user(), $request->validated()));
    }
}
