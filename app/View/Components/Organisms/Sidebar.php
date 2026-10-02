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

    public function isActive(string $route): bool
    {
        return request()->routeIs($route);
    }

    public function render(): View
    {
        return view('components.organisms.sidebar');
    }
}
