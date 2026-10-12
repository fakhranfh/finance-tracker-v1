<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GoalPageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('goals.table.name'), 'sort' => 'name'],
            ['label' => __('goals.table.target_amount'), 'sort' => 'target_amount'],
            ['label' => __('goals.table.saved_amount'), 'sort' => 'saved_amount'],
            ['label' => __('goals.table.desired_date'), 'sort' => 'desired_date'],
            __('goals.table.color'),
            __('goals.table.icon'),
            __('goals.table.note'),
            __('goals.table.actions'),
        ];

        return view('goals.index', ['tableHeaders' => $tableHeaders, 'icons' => config('goal.icons')]);
    }

    public function create(): View
    {
        return view('goals.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('goals.form', ['id' => $id]);
    }
}
