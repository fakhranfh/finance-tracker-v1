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
        $this->navItems = config('sidebar.nav');
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
