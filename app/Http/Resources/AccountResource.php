<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Account $account */
        $account = $this->resource;

        return [
            'id' => $account->id,
            'name' => $account->name,
            'balance' => $account->balance,
            'last_balance' => $account->last_balance,
            'created_at' => $account->created_at?->toIso8601String(),
        ];
    }
}
