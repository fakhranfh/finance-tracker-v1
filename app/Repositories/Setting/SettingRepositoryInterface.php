<?php

namespace App\Repositories\Setting;

interface SettingRepositoryInterface
{
    public function get(string $key): ?string;

    public function put(string $key, ?string $value): void;
}
