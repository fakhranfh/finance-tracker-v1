<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Debts and receivables share one table; the route default `type` picks which half a page shows.
 */
class DebtPageController extends Controller
{
    public function index(string $type): View
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

        return view('debts.index', ['tableHeaders' => $tableHeaders, 'type' => $type]);
    }

    public function create(string $type): View
    {
        return view('debts.form', ['id' => null, 'type' => $type]);
    }

    public function edit(int $id, string $type): View
    {
        return view('debts.form', ['id' => $id, 'type' => $type]);
    }
}
