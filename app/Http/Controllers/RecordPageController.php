<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use Illuminate\Support\Facades\Lang;
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

    public function import(): View
    {
        return view('records.import', [
            'fields' => $this->importFields(),
            'labels' => $this->importLabels(),
            'typeLabels' => $this->typeLabels(),
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
     * The record fields a spreadsheet column can be mapped to.
     *
     * @return array<int, array{key: string, label: string, hint: string|null, required: bool}>
     */
    private function importFields(): array
    {
        return collect(['recorded_at', 'amount', 'type', 'account', 'to_account', 'category', 'note'])
            ->map(fn (string $key) => [
                'key' => $key,
                'label' => __("records.import.fields.{$key}"),
                'hint' => Lang::has("records.import.field_hints.{$key}") ? __("records.import.field_hints.{$key}") : null,
                'required' => in_array($key, ['recorded_at', 'amount'], true),
            ])
            ->all();
    }

    /**
     * Texts the import page's JS shows.
     *
     * @return array<string, mixed>
     */
    private function importLabels(): array
    {
        return [
            'steps' => array_values(__('records.import.steps')),
            'stepLabel' => __('records.import.step_label', ['step' => ':step', 'total' => ':total', 'name' => ':name']),
            'example' => __('records.import.example', ['value' => ':value']),
            'createAccount' => __('records.import.create_account', ['name' => ':name']),
            'existingAccounts' => __('records.import.existing_accounts'),
            'unmappedAccount' => __('records.import.unmapped_account'),
            'uncategorized' => __('records.fields.no_category'),
            'createCategory' => __('records.import.create_category', ['name' => ':name']),
            'createdCategories' => __('records.import.created_categories', ['names' => ':names']),
            'incomeCategories' => __('records.import.income_categories'),
            'expenseCategories' => __('records.import.expense_categories'),
            'imported' => __('records.import.imported', ['count' => ':count']),
            'skipped' => __('records.import.skipped', ['count' => ':count']),
            'createdAccounts' => __('records.import.created_accounts', ['names' => ':names']),
            'errorRow' => __('records.import.error_row', ['row' => ':row', 'message' => ':message']),
            'failed' => __('records.import.failed'),
            'typesFound' => __('records.import.types_found', ['values' => ':values']),
            'rowCount' => __('records.import.row_count', ['count' => ':count']),
            'resume' => __('records.import.resume'),
            'discard' => __('records.import.discard'),
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
