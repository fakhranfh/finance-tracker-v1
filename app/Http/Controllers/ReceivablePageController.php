<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReceivablePageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('receivables.table.name'), 'sort' => 'name'],
            ['label' => __('receivables.table.amount'), 'sort' => 'amount'],
            ['label' => __('receivables.table.paid_amount'), 'sort' => 'paid_amount'],
            ['label' => __('receivables.table.due_date'), 'sort' => 'due_date'],
            ['label' => __('receivables.table.notes'), 'sort' => 'notes'],
            ['label' => __('receivables.table.is_paid'), 'sort' => 'is_paid'],
            ['label' => __('receivables.table.created_at'), 'sort' => 'created_at'],
            __('receivables.table.actions'),
        ];

        return view('receivables.index', ['tableHeaders' => $tableHeaders]);
    }

    public function create(): View
    {
        return view('receivables.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('receivables.form', ['id' => $id]);
    }
}
