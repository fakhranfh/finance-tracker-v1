<?php

namespace App\View\Components\Organisms;

use Illuminate\View\Component;
use Illuminate\View\View;

class FeatureGuide extends Component
{
    public array $features;

    public array $icons;

    public string $name;

    public function __construct()
    {
        $this->features = array_values(array_filter(
            config('sidebar.nav'),
            fn (array $item) => isset($item['description']),
        ));
        $this->icons = config('sidebar.icons');
        $this->name = auth()->user()->name;
    }

    public function render(): View
    {
        return view('components.organisms.feature-guide');
    }
}
