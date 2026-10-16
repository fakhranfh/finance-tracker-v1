<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WebhookPageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('webhooks.table.name'), 'sort' => 'name'],
            ['label' => __('webhooks.table.is_active'), 'sort' => 'is_active'],
            ['label' => __('webhooks.table.created_at'), 'sort' => 'created_at'],
            __('webhooks.table.actions'),
        ];

        return view('webhooks.index', ['tableHeaders' => $tableHeaders]);
    }

    public function create(): View
    {
        return view('webhooks.form', ['id' => null]);
    }

    public function test(int $id): View
    {
        return view('webhooks.test', ['id' => $id]);
    }

    public function logs(int $id): View
    {
        $tableHeaders = [
            ['label' => __('webhooks.logs.table.time'), 'sort' => 'created_at'],
            ['label' => __('webhooks.logs.table.status'), 'sort' => 'status_code'],
            __('webhooks.logs.table.message'),
            __('webhooks.logs.table.origin'),
            __('webhooks.logs.table.ip'),
            __('webhooks.logs.table.payload'),
            '',
        ];

        return view('webhooks.logs', ['id' => $id, 'tableHeaders' => $tableHeaders]);
    }

    public function edit(int $id): View
    {
        return view('webhooks.form', ['id' => $id]);
    }
}
