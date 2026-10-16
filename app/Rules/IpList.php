<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A newline- or comma-separated list of IP addresses (IPv4 or IPv6), each optionally a CIDR range like "203.0.113.0/24".
 */
class IpList implements ValidationRule
{
    public const MAX_IPS = 20;

    /**
     * @return array<int, string>
     */
    public static function parse(?string $value): array
    {
        $entries = preg_split('/[\s,]+/', strtolower((string) $value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($entries ?: []));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $entries = self::parse(is_string($value) ? $value : '');

        if (count($entries) > self::MAX_IPS) {
            $fail(__('webhooks.errors.too_many_ips', ['max' => self::MAX_IPS]));

            return;
        }

        foreach ($entries as $entry) {
            if (! self::isValid($entry)) {
                $fail(__('webhooks.errors.invalid_ip', ['ip' => $entry]));

                return;
            }
        }
    }

    private static function isValid(string $entry): bool
    {
        [$address, $bits] = array_pad(explode('/', $entry, 2), 2, null);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($bits === null) {
            return true;
        }

        $max = str_contains($address, ':') ? 128 : 32;

        return ctype_digit($bits) && (int) $bits <= $max;
    }
}
