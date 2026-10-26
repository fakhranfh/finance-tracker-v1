<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Sub debts of a debt or receivable; the route default `type` picks which of the two the page belongs to.
 */
class SubDebtPageController extends Controller
{
    public function index(int $debtId, string $type): View
    {
        $tableHeaders = [
            ['label' => __('sub-debts.table.name'), 'sort' => 'name'],
            ['label' => __('sub-debts.table.amount'), 'sort' => 'amount'],
            ['label' => __('sub-debts.table.paid_amount'), 'sort' => 'paid_amount'],
            ['label' => __('sub-debts.table.due_date'), 'sort' => 'due_date'],
            ['label' => __('sub-debts.table.is_paid'), 'sort' => 'is_paid'],
            ['label' => __('sub-debts.table.note'), 'sort' => 'note'],
            __('sub-debts.table.actions'),
        ];

        return view('sub-debts.index', ['debtId' => $debtId, 'type' => $type, 'tableHeaders' => $tableHeaders]);
    }

    public function edit(int $debtId, string $type): View
    {
        return view('sub-debts.form', ['debtId' => $debtId, 'type' => $type]);
    }

    public function create(int $debtId, string $type): View
    {
        return view('sub-debts.item-form', ['debtId' => $debtId, 'id' => null, 'type' => $type]);
    }

    public function editItem(int $debtId, int $id, string $type): View
    {
        return view('sub-debts.item-form', ['debtId' => $debtId, 'id' => $id, 'type' => $type]);
    }
}
