<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use App\Services\AccountSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountPageController extends Controller
{
    public function index(): View
    {
        return view('accounts.index');
    }

    public function create(): View
    {
        return view('accounts.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('accounts.form', ['id' => $id]);
    }

    public function summary(Request $request, int $id): View
    {
        return view('accounts.summary', [
            'id' => $id,
            'presets' => $this->presetOptions(),
            'typeLabels' => $this->typeLabels(),
            'tableHeaders' => $this->recordTableHeaders(),
            'range' => [
                'preset' => in_array((string) $request->query('range'), AccountSummaryService::PRESETS, true)
                    ? (string) $request->query('range')
                    : 'this_month',
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function presetOptions(): array
    {
        return [
            'this_month' => __('accounts.summary.presets.this_month'),
            'last_month' => __('accounts.summary.presets.last_month'),
            'last_30_days' => __('accounts.summary.presets.last_30_days'),
            'this_year' => __('accounts.summary.presets.this_year'),
            'all' => __('accounts.summary.presets.all'),
            'custom' => __('accounts.summary.presets.custom'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return collect(RecordType::cases())->mapWithKeys(fn (RecordType $type) => [$type->value => $type->label()])->all();
    }

    /**
     * @return array<int, array<string, mixed>|string>
     */
    private function recordTableHeaders(): array
    {
        return [
            ['label' => __('records.table.recorded_at'), 'sort' => 'recorded_at'],
            ['label' => __('records.table.type'), 'sort' => 'type'],
            __('records.table.category'),
            __('records.table.account'),
            ['label' => __('records.table.amount'), 'sort' => 'amount'],
            ['label' => __('records.table.note'), 'sort' => 'note'],
            __('records.table.actions'),
        ];
    }
}
