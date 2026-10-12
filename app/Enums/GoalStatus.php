<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Reached = 'reached';

    public function label(): string
    {
        return __('goals.statuses.'.$this->value);
    }
}
