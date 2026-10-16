<?php

namespace App\Http\Resources;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookLog
 */
class WebhookLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WebhookLog $log */
        $log = $this->resource;

        return [
            'id' => $log->id,
            'status_code' => $log->status_code,
            'ip' => $log->ip,
            'origin' => $log->origin,
            'message' => $log->message,
            'payload' => $log->payload,
            'record_id' => $log->record_id,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }
}
