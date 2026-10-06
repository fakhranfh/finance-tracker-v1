<?php

namespace App\View\Components\Organisms;

use Illuminate\View\Component;
use Illuminate\View\View;

class AccountCards extends Component
{
    /** @var array<int, string> */
    public array $colors;

    /** @var array<string, string> */
    public array $icons;

    /** @var array<string, string> */
    public array $iconLabels;

    public function __construct()
    {
        $this->colors = config('account.colors');
        $this->icons = config('account.icons');
        $this->iconLabels = collect(array_keys($this->icons))
            ->mapWithKeys(fn (string $icon): array => [$icon => __("accounts.appearance.icons.{$icon}")])
            ->all();
    }

    public function render(): View
    {
        return view('components.organisms.account-cards');
    }
}
