<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
            'account' => Account::query()->findOrFail($id),
            'presets' => $this->presetOptions(),
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
}
