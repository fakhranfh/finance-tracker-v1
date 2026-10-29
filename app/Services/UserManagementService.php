<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected AuthService $authService,
    ) {}

    /**
     * List users, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single user by ID.
     */
    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Create a new user.
     *
     * @param  array{name: string, email: string, password: string, role?: string}  $data
     */
    public function create(array $data): User
    {
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Added by an admin, who vouches for the address.
        $user->markEmailAsVerified();

        $this->userRepository->syncRole($user, $data['role'] ?? RoleName::User->value);

        return $user;
    }

    /**
     * Update an existing user. The password is only rehashed when provided.
     *
     * @param  array{name: string, email: string, password?: string, role?: string}  $data
     */
    public function update(User $user, array $data): User
    {
        if (! empty($data['role'])) {
            $this->userRepository->syncRole($user, $data['role']);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->update($user, $payload);
    }

    /**
     * Update the signed-in user's own profile. A changed email must be verified again.
     *
     * @param  array{name: string, email: string, password?: string}  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $emailChanged = $data['email'] !== $user->email;

        $user = $this->update($user, $data);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $this->authService->sendVerificationEmail($user);
        }

        return $user;
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): void
    {
        $this->userRepository->delete($user);
    }

    /**
     * Delete multiple users by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->userRepository->deleteMany($ids);
    }

    /**
     * Count users matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->userRepository->countMatching($search, $exceptId);
    }

    /**
     * Delete every user matching the search filter (all users when null).
     */
    public function deleteMatching(?string $search = null, ?int $exceptId = null): void
    {
        $this->userRepository->deleteMatching($search, $exceptId);
    }
}
