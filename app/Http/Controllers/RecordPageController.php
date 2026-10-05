<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RecordPageController extends Controller
{
    public function index(): View
    {
        $typeLabels = $this->typeLabels();
        $accounts = Account::query()->orderBy('name')->get(['id', 'name']);

        return view('records.index', [
            'typeLabels' => $typeLabels,
            'hasAccounts' => Account::query()->exists(),
            'accounts' => $accounts,
            'accountOptions' => $this->accountOptions($accounts),
            'tableHeaders' => $this->tableHeaders(),
            'filterConfig' => $this->filterConfig($typeLabels, $accounts),
        ]);
    }

    public function create(): View
    {
        return view('records.form', $this->formData(null));
    }

    public function edit(int $id): View
    {
        return view('records.form', $this->formData($id));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?int $id): array
    {
        return [
            'id' => $id,
            'typeLabels' => $this->typeLabels(),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
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
     * @return array<int, array<string, mixed>>
     */
    private function tableHeaders(): array
    {
        return [
            ['label' => __('records.table.recorded_at'), 'sort' => 'recorded_at'],
            ['label' => __('records.table.type'), 'sort' => 'type'],
            __('records.table.account'),
            ['label' => __('records.table.amount'), 'sort' => 'amount'],
            ['label' => __('records.table.note'), 'sort' => 'note'],
            __('records.table.actions'),
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, array{value: int, label: string, searchText: string}>
     */
    private function accountOptions(Collection $accounts): array
    {
        return $accounts
            ->map(fn (Account $account) => ['value' => $account->id, 'label' => $account->name, 'searchText' => mb_strtolower($account->name)])
            ->values()
            ->all();
    }

    /**
     * Filter bar definition consumed by the table organism.
     *
     * @param  array<string, string>  $typeLabels
     * @param  Collection<int, Account>  $accounts
     * @return array<int, array<string, mixed>>
     */
    private function filterConfig(array $typeLabels, Collection $accounts): array
    {
        return [
            [
                'type' => 'multi',
                'key' => 'type',
                'label' => __('records.filters.type'),
                'allLabel' => __('records.filters.all_types'),
                'selectedTemplate' => __('records.filters.types_selected', ['count' => ':count']),
                'searchPlaceholder' => __('records.filters.search_types'),
                'selectAllLabel' => __('records.filters.select_all'),
                'deselectAllLabel' => __('records.filters.deselect_all'),
                'options' => collect($typeLabels)
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label, 'searchText' => mb_strtolower($label)])
                    ->values()
                    ->all(),
            ],
            [
                'type' => 'multi',
                'key' => 'account_ids',
                'label' => __('records.filters.account'),
                'allLabel' => __('records.filters.all_accounts'),
                'selectedTemplate' => __('records.filters.accounts_selected', ['count' => ':count']),
                'searchPlaceholder' => __('records.filters.search_accounts'),
                'selectAllLabel' => __('records.filters.select_all'),
                'deselectAllLabel' => __('records.filters.deselect_all'),
                'options' => $this->accountOptions($accounts),
            ],
            ['type' => 'date', 'key' => 'date_from', 'label' => __('records.filters.date_from')],
            ['type' => 'date', 'key' => 'date_to', 'label' => __('records.filters.date_to')],
        ];
    }
}
