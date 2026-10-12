<?php

namespace App\Http\Controllers;

use App\Enums\GoalStatus;
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
            ['label' => __('goals.table.status'), 'sort' => 'status'],
            __('goals.table.color'),
            __('goals.table.icon'),
            __('goals.table.note'),
            __('goals.table.actions'),
        ];

        $statusLabels = collect(GoalStatus::cases())->mapWithKeys(fn (GoalStatus $status) => [$status->value => $status->label()]);

        return view('goals.index', ['tableHeaders' => $tableHeaders, 'icons' => config('goal.icons'), 'statusLabels' => $statusLabels]);
    }

    public function create(): View
    {
        return view('goals.form', ['id' => null, 'statuses' => GoalStatus::cases()]);
    }

    public function show(int $id): View
    {
        $fields = ['estimated_completion', 'desired_date', 'note'];
        $savingFilters = [
            ['type' => 'date', 'key' => 'date_from', 'label' => __('goals.history.date_from')],
            ['type' => 'date', 'key' => 'date_to', 'label' => __('goals.history.date_to')],
        ];
        $savingHeaders = [
            ['label' => __('goals.history.date'), 'sort' => 'created_at'],
            ['label' => __('goals.history.amount'), 'sort' => 'amount'],
            __('goals.table.actions'),
        ];

        return view('goals.show', ['id' => $id, 'icons' => config('goal.icons'), 'fields' => $fields, 'savingHeaders' => $savingHeaders, 'savingFilters' => $savingFilters]);
    }

    public function edit(int $id): View
    {
        return view('goals.form', ['id' => $id, 'statuses' => GoalStatus::cases()]);
    }
}
