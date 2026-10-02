<?php

namespace App\View\Components\Atoms;

use Illuminate\View\Component;
use Illuminate\View\View;

class LanguageSwitcher extends Component
{
    public string $current;

    public function __construct()
    {
        $this->current = app()->getLocale();
    }

    public function render(): View
    {
        return view('components.atoms.language-switcher');
    }
}
