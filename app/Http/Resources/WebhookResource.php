<?php

namespace App\Http\Resources;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Webhook
 */
class WebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Webhook $webhook */
        $webhook = $this->resource;

        return [
            'id' => $webhook->id,
            'name' => $webhook->name,
            'token' => $webhook->token,
            'url' => route('api.webhooks.records.store'),
            'is_active' => $webhook->is_active,
            'allowed_ips' => $webhook->allowed_ips ?? [],
            'allowed_account_ids' => $webhook->allowed_account_ids ?? [],
            'allowed_types' => $webhook->allowed_types ?? [],
            'created_at' => $webhook->created_at?->toIso8601String(),
        ];
    }
}
