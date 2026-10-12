<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SubGoalPageController extends Controller
{
    public function index(int $goalId): View
    {
        $tableHeaders = [
            ['label' => __('sub-goals.table.name'), 'sort' => 'name'],
            ['label' => __('sub-goals.table.target_amount'), 'sort' => 'target_amount'],
            ['label' => __('sub-goals.table.saved_amount'), 'sort' => 'saved_amount'],
            ['label' => __('sub-goals.table.desired_date'), 'sort' => 'desired_date'],
            ['label' => __('sub-goals.table.is_done'), 'sort' => 'is_done'],
            ['label' => __('sub-goals.table.note'), 'sort' => 'note'],
            __('sub-goals.table.actions'),
        ];

        return view('sub-goals.index', ['goalId' => $goalId, 'tableHeaders' => $tableHeaders]);
    }

    public function show(int $goalId, int $id): View
    {
        $fields = ['goal', 'target_amount', 'saved_amount', 'desired_date', 'status', 'note'];
        $savingHeaders = [
            ['label' => __('sub-goals.show_page.history_date'), 'sort' => 'created_at'],
            ['label' => __('sub-goals.show_page.history_amount'), 'sort' => 'amount'],
            '',
        ];

        return view('sub-goals.show', ['goalId' => $goalId, 'id' => $id, 'fields' => $fields, 'savingHeaders' => $savingHeaders]);
    }

    public function editItem(int $goalId, int $id): View
    {
        return view('sub-goals.item-form', ['goalId' => $goalId, 'id' => $id]);
    }

    public function edit(int $goalId): View
    {
        return view('sub-goals.form', ['goalId' => $goalId]);
    }
}
