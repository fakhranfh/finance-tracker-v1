<?php

namespace App\View\Components\Organisms;

use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public array $navItems;

    public array $icons;

    public function __construct()
    {
        $this->navItems = array_values(array_filter(
            config('sidebar.nav'),
            fn (array $item) => ! isset($item['role']) || auth()->user()?->hasRole($item['role']),
        ));
        $this->icons = config('sidebar.icons');
    }

    public function isActive(string|array $route): bool
    {
        return request()->routeIs(...(array) $route);
    }

    public function render(): View
    {
        return view('components.organisms.sidebar');
    }
}
