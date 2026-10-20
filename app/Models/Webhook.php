<?php

namespace App\Models;

use App\Models\Concerns\HasUserTimezone;
use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * @property bool $can_create_accounts
 * @property array<int, string>|null $allowed_ips
 * @property array<int, int>|null $allowed_account_ids
 * @property array<int, string>|null $allowed_types
 */
#[Fillable(['name', 'token', 'is_active', 'can_create_accounts', 'allowed_ips', 'allowed_account_ids', 'allowed_types'])]
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
            'can_create_accounts' => 'boolean',
            'allowed_ips' => 'array',
            'allowed_account_ids' => 'array',
            'allowed_types' => 'array',
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

    /**
     * Whether records of this type may be added. Every type is allowed when none are listed.
     */
    public function allowsType(string $type): bool
    {
        $allowed = $this->allowed_types ?? [];

        return $allowed === [] || in_array($type, $allowed, true);
    }

    /**
     * Whether records may be added to this account. Every account is allowed when none are listed.
     */
    public function allowsAccount(int $accountId): bool
    {
        $allowed = $this->allowed_account_ids ?? [];

        return $allowed === [] || in_array($accountId, $allowed, true);
    }
}
