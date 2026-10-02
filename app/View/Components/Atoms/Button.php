<?php

namespace App\View\Components\Atoms;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    public string $classes;

    public function __construct(public ?string $href = null, string $variant = 'primary')
    {
        $variants = [
            'primary' => 'bg-primary text-on-primary hover:bg-primary-container',
            'secondary' => 'bg-surface text-on-surface border border-outline-variant hover:bg-surface-container',
            'danger' => 'bg-error text-on-error hover:bg-error/90',
            'inverse' => 'bg-on-primary text-primary hover:bg-surface-bright',
        ];

        $this->classes = 'inline-flex items-center justify-center gap-2 rounded-md px-5 py-2.5 font-body text-body-md font-medium transition-colors cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 '
            . ($variants[$variant] ?? $variants['primary']);
    }

    public function render(): View
    {
        return view('components.atoms.button');
    }
}
