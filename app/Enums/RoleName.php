<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return __('users.roles.'.$this->value);
    }
}
