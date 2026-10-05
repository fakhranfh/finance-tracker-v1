<?php

namespace App\View\Components\Organisms;

use Illuminate\View\Component;
use Illuminate\View\View;

class Table extends Component
{
    public string $bulkDeleteLabel;

    public string $selectedLabel;

    public string $emptyLabel;

    /** Empty disables the total line. */
    public string $totalLabel;

    public string $clearFiltersLabel;

    public int $columnCount;

    /** @var array<string, mixed> */
    public array $dataAttributes;

    /** @var array<int, array{label: string, sort: ?string}> */
    public array $headerCells;

    /** @var array<int, array<string, mixed>> */
    public array $filterFields;

    public string $filterLabelClass = 'mb-1 block font-body text-xs text-on-surface-variant';

    public int $skeletonRows = 5;

    /** Width class per body cell, actions column last. @var array<int, string> */
    public array $skeletonCells = [];

    /** Value width per mobile card row, the actions column has no row. @var array<int, string> */
    public array $skeletonCards = [];

    /**
     * @param  array<int, string|array{label: string, sort?: string}>  $headers
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $filters
     */
    public function __construct(
        array $headers = [],
        public bool $selectable = true,
        array $config = [],
        array $filters = [],
        public string $filtersId = 'table-filters',
    ) {
        $labels = $config['labels'] ?? [];

        $this->bulkDeleteLabel = $labels['bulkDelete'] ?? 'Delete selected';
        $this->selectedLabel = $labels['selected'] ?? 'selected';
        $this->emptyLabel = $labels['empty'] ?? 'No data';
        $this->totalLabel = $labels['total'] ?? '';
        $this->clearFiltersLabel = $labels['clearFilters'] ?? 'Clear filters';
        $this->columnCount = count($headers) + ($selectable ? 1 : 0);

        $this->dataAttributes = [
            'data-label-select-all-matching' => $labels['selectAllMatching'] ?? '',
            'data-label-all-selected' => $labels['allSelected'] ?? '',
            'data-label-clear-selection' => $labels['clearSelection'] ?? '',
            'data-label-expand' => $labels['expand'] ?? '',
            'data-label-collapse' => $labels['collapse'] ?? '',
            'data-label-edit' => $labels['edit'] ?? '',
            'data-label-delete' => $labels['delete'] ?? '',
            'data-locked-id' => $config['lockedId'] ?? '',
        ];

        $this->headerCells = array_map(fn ($header) => is_array($header)
            ? ['label' => $header['label'], 'sort' => $header['sort'] ?? null]
            : ['label' => $header, 'sort' => null], $headers);

        $this->filterFields = array_map(fn ($filter) => $filter + ['id' => 'filter-'.$filter['key']], $filters);

        $widths = ['w-32', 'w-20', 'w-48', 'w-28', 'w-24'];

        if ($this->headerCells !== []) {
            $this->skeletonCells = array_map(fn ($i) => $widths[$i % count($widths)], array_keys($this->headerCells));
            $this->skeletonCells[array_key_last($this->skeletonCells)] = 'w-16';
            $this->skeletonCards = array_slice($this->skeletonCells, 0, -1);
        }
    }

    public function render(): View
    {
        return view('components.organisms.table');
    }
}
