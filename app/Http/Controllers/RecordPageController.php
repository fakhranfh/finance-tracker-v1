<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use Illuminate\View\View;

class RecordPageController extends Controller
{
    public function index(): View
    {
        $typeLabels = $this->typeLabels();

        return view('records.index', [
            'typeLabels' => $typeLabels,
            'tableHeaders' => $this->tableHeaders(),
            'filterConfig' => $this->filterConfig($typeLabels),
        ]);
    }

    public function create(): View
    {
        return view('records.form', ['id' => null, 'typeLabels' => $this->typeLabels()]);
    }

    public function edit(int $id): View
    {
        return view('records.form', ['id' => $id, 'typeLabels' => $this->typeLabels()]);
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
    private function tableHeaders(): array
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

    /**
     * Filter bar definition consumed by the table organism. The account and category options
     * are loaded from the API by the page's JS.
     *
     * @param  array<string, string>  $typeLabels
     * @return array<int, array<string, mixed>>
     */
    private function filterConfig(array $typeLabels): array
    {
        return [
            [
                'type' => 'select',
                'key' => 'period',
                'label' => __('records.filters.period'),
                'allLabel' => __('records.filters.all_time'),
                'options' => collect(['this_month', 'last_month', 'last_30_days', 'this_year'])
                    ->map(fn (string $preset) => ['value' => $preset, 'label' => __("records.filters.periods.{$preset}")])
                    ->all(),
            ],
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
                'options' => [],
            ],
            [
                'type' => 'multi',
                'key' => 'category_ids',
                'label' => __('records.filters.category'),
                'allLabel' => __('records.filters.all_categories'),
                'selectedTemplate' => __('records.filters.categories_selected', ['count' => ':count']),
                'searchPlaceholder' => __('records.filters.search_categories'),
                'selectAllLabel' => __('records.filters.select_all'),
                'deselectAllLabel' => __('records.filters.deselect_all'),
                'options' => [],
            ],
            ['type' => 'date', 'key' => 'date_from', 'label' => __('records.filters.date_from')],
            ['type' => 'date', 'key' => 'date_to', 'label' => __('records.filters.date_to')],
            ['type' => 'number', 'key' => 'amount_min', 'label' => __('records.filters.amount_min')],
            ['type' => 'number', 'key' => 'amount_max', 'label' => __('records.filters.amount_max')],
        ];
    }
}
