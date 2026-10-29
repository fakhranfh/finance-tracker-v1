<?php

namespace App\Repositories\Setting;

use App\Models\Setting;

class SettingRepository implements SettingRepositoryInterface
{
    public function get(string $key): ?string
    {
        return Setting::query()->where('key', $key)->value('value');
    }

    public function put(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
