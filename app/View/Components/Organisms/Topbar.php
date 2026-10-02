<?php

namespace App\View\Components\Organisms;

use Illuminate\View\Component;
use Illuminate\View\View;

class Topbar extends Component
{
    public array $icons;

    public function __construct()
    {
        $this->icons = config('topbar.icons');
    }

    public function render(): View
    {
        return view('components.organisms.topbar');
    }
}
