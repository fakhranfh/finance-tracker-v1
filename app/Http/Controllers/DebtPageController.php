<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DebtPageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('debts.table.name'), 'sort' => 'name'],
            ['label' => __('debts.table.amount'), 'sort' => 'amount'],
            ['label' => __('debts.table.paid_amount'), 'sort' => 'paid_amount'],
            ['label' => __('debts.table.due_date'), 'sort' => 'due_date'],
            ['label' => __('debts.table.notes'), 'sort' => 'notes'],
            ['label' => __('debts.table.is_paid'), 'sort' => 'is_paid'],
            ['label' => __('debts.table.created_at'), 'sort' => 'created_at'],
            __('debts.table.actions'),
        ];

        return view('debts.index', ['tableHeaders' => $tableHeaders]);
    }

    public function create(): View
    {
        return view('debts.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('debts.form', ['id' => $id]);
    }
}
