<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Payments of a debt or receivable; the route default `type` picks which of the two the page belongs to.
 */
class DebtPaymentPageController extends Controller
{
    public function index(int $debtId, string $type): View
    {
        $tableHeaders = [
            ['label' => __('debt-payments.table.recorded_at'), 'sort' => 'recorded_at'],
            __('debt-payments.table.account'),
            __('debt-payments.table.category'),
            ['label' => __('debt-payments.table.amount'), 'sort' => 'amount'],
            ['label' => __('debt-payments.table.note'), 'sort' => 'note'],
            __('debt-payments.table.sub_debt'),
            __('debt-payments.table.actions'),
        ];

        return view('debt-payments.index', ['debtId' => $debtId, 'type' => $type, 'tableHeaders' => $tableHeaders]);
    }

    public function create(int $debtId, string $type): View
    {
        return view('debt-payments.form', ['debtId' => $debtId, 'type' => $type]);
    }

    public function existing(int $debtId, string $type): View
    {
        $tableHeaders = [
            ['label' => __('debt-payments.table.recorded_at'), 'sort' => 'recorded_at'],
            __('debt-payments.table.account'),
            __('debt-payments.table.category'),
            ['label' => __('debt-payments.table.amount'), 'sort' => 'amount'],
            ['label' => __('debt-payments.table.note'), 'sort' => 'note'],
            __('debt-payments.table.actions'),
        ];

        return view('debt-payments.existing', ['debtId' => $debtId, 'type' => $type, 'tableHeaders' => $tableHeaders]);
    }
}
