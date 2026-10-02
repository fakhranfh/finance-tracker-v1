<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Converts the model's datetime attributes to the authenticated user's
 * stored timezone whenever they are accessed, instead of displaying
 * raw UTC values.
 */
trait HasUserTimezone
{
    /**
     * Get an attribute's value, converting datetime attributes to the
     * authenticated user's timezone.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value instanceof CarbonInterface && $this->isUserTimezoneAttribute($key)) {
            return $value->clone()->setTimezone($this->resolveUserTimezone());
        }

        return $value;
    }

    /**
     * Determine whether the given attribute is a date/datetime attribute.
     */
    protected function isUserTimezoneAttribute(string $key): bool
    {
        $cast = $this->getCasts()[$key] ?? null;

        if (in_array($key, ['created_at', 'updated_at'], true)) {
            return true;
        }

        return is_string($cast) && str_starts_with($cast, 'date');
    }

    /**
     * Resolve the timezone to convert datetime attributes into.
     */
    protected function resolveUserTimezone(): string
    {
        $user = Auth::user();

        if ($user && ! empty($user->timezone)) {
            return $user->timezone;
        }

        return config('app.timezone', 'UTC');
    }
}
