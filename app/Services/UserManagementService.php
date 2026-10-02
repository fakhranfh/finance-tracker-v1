<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
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
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User
    {
        return $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Update an existing user. The password is only rehashed when provided.
     *
     * @param  array{name: string, email: string, password?: string}  $data
     */
    public function update(User $user, array $data): User
    {
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
