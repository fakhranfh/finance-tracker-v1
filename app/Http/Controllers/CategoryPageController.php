<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use Illuminate\View\View;

class CategoryPageController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'typeLabels' => $this->typeLabels(),
            'icons' => config('category.icons'),
            'tableHeaders' => [
                ['label' => __('categories.table.name'), 'sort' => 'name'],
                ['label' => __('categories.table.type'), 'sort' => 'type'],
                ['label' => __('categories.table.color'), 'sort' => 'color'],
                ['label' => __('categories.table.icon'), 'sort' => 'icon'],
                ['label' => __('categories.table.created_at'), 'sort' => 'created_at'],
                __('categories.table.actions'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('categories.form', ['id' => null, 'typeLabels' => $this->typeLabels()]);
    }

    public function edit(int $id): View
    {
        return view('categories.form', ['id' => $id, 'typeLabels' => $this->typeLabels()]);
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return collect(CategoryType::cases())->mapWithKeys(fn (CategoryType $type) => [$type->value => $type->label()])->all();
    }
}
