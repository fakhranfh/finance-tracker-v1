<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use Illuminate\Http\Request;
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
        return view('webhooks.form', ['id' => null, 'recordTypes' => $this->recordTypes()]);
    }

    public function test(Request $request, int $id): View
    {
        return view('webhooks.test', ['id' => $id] + $this->backLink($request, $id));
    }

    public function show(int $id): View
    {
        $fields = [
            'status' => __('webhooks.show.status'),
            'allowed_ips' => __('webhooks.fields.allowed_ips'),
            'allowed_account_ids' => __('webhooks.fields.allowed_account_ids'),
            'allowed_types' => __('webhooks.fields.allowed_types'),
            'created_at' => __('webhooks.table.created_at'),
        ];

        return view('webhooks.show', ['id' => $id, 'fields' => $fields, 'recordTypes' => $this->recordTypes()]);
    }

    public function logs(Request $request, int $id): View
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

        return view('webhooks.logs', ['id' => $id, 'tableHeaders' => $tableHeaders] + $this->backLink($request, $id));
    }

    public function edit(int $id): View
    {
        return view('webhooks.form', ['id' => $id, 'recordTypes' => $this->recordTypes()]);
    }

    /**
     * Where the Back link of the test and logs pages leads: the page they were opened from
     * (?from=show|edit|index), the edit page by default.
     *
     * @return array{from: string, backUrl: string, backLabel: string}
     */
    private function backLink(Request $request, int $id): array
    {
        $from = in_array($request->query('from'), ['show', 'edit', 'index'], true) ? $request->query('from') : 'edit';

        return [
            'from' => $from,
            'backUrl' => match ($from) {
                'show' => route('webhooks.show', $id),
                'index' => route('webhooks.index'),
                default => route('webhooks.edit', $id),
            },
            'backLabel' => __('webhooks.back_to.'.$from),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function recordTypes(): array
    {
        return collect(RecordType::cases())->mapWithKeys(fn (RecordType $type) => [$type->value => $type->label()])->all();
    }
}
