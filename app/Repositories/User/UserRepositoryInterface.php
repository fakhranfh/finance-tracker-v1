<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by ID.
     */
    public function find(int $id): ?User;

    /**
     * Get the oldest user, if any.
     */
    public function first(): ?User;

    /**
     * Paginate users, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete users by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every user matching the search filter (all users when null).
     */
    public function deleteMatching(?string $search = null, ?int $exceptId = null): void;

    /**
     * Count users matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user.
     */
    public function delete(User $user): void;
}
