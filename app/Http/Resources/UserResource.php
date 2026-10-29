<?php

namespace App\Http\Resources;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $role = ($name = $user->getRoleNames()->first()) ? RoleName::tryFrom($name) : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => $user->timezone,
            'role' => $role?->value,
            'role_label' => $role?->label(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
