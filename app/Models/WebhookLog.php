<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwnerThrough;
use App\Models\Concerns\HasUserTimezone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One call to a webhook public endpoint and how it ended.
 *
 * @property array<string, mixed>|null $payload
 */
#[Fillable(['webhook_id', 'status_code', 'ip', 'origin', 'message', 'payload', 'record_id'])]
class WebhookLog extends Model
{
    use BelongsToOwnerThrough, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @return array{0: class-string<Model>, 1: string}
     */
    protected static function ownerParent(): array
    {
        return [Webhook::class, 'webhook_id'];
    }
}
