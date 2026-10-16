<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * @property array<int, string>|null $allowed_ips
 */
#[Fillable(['name', 'token', 'is_active', 'allowed_ips'])]
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory, HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_ips' => 'array',
        ];
    }

    /**
     * Whether a request from the given IP address may use this webhook. With no allowed IPs
     * every address is accepted; otherwise it must match a listed address or CIDR range.
     */
    public function allowsIp(?string $ip): bool
    {
        $allowed = $this->allowed_ips ?? [];

        if ($allowed === []) {
            return true;
        }

        return $ip !== null && IpUtils::checkIp($ip, $allowed);
    }
}
