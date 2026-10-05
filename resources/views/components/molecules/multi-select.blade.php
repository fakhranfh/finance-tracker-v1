@props([
    'id',
    'name',
    'options' => [],
    'allLabel',
    'selectedTemplate',
    'searchPlaceholder',
    'selectAllLabel',
    'deselectAllLabel',
    'checked' => false,
    'summaryClass' => 'flex cursor-pointer list-none items-center justify-between gap-2 rounded-md border border-outline-variant bg-surface px-4 py-2.5 font-body text-body-md text-on-surface',
])

<details class="relative" id="{{ $id }}" data-filter-multi>
    <summary class="{{ $summaryClass }}">
        <span data-filter-multi-summary data-all-label="{{ $allLabel }}" data-selected-template="{{ $selectedTemplate }}">{{ $allLabel }}</span>
        <span aria-hidden="true">▾</span>
    </summary>
    <div class="absolute z-20 mt-2 w-full rounded-lg border border-outline-variant bg-surface p-2 shadow-lg">
        <div class="mb-2 flex flex-col gap-2">
            <x-atoms.input type="search" name="{{ $name }}_search" id="{{ $id }}-search" placeholder="{{ $searchPlaceholder }}" data-filter-multi-search />
            <div class="flex gap-2 font-body text-xs">
                <button type="button" class="rounded border border-outline-variant px-2 py-1 text-on-surface hover:bg-surface-container" data-filter-multi-select-all>{{ $selectAllLabel }}</button>
                <button type="button" class="rounded border border-outline-variant px-2 py-1 text-on-surface hover:bg-surface-container" data-filter-multi-deselect-all>{{ $deselectAllLabel }}</button>
            </div>
        </div>
        <div class="max-h-48 overflow-y-auto">
            @foreach ($options as $option)
                <label class="flex cursor-pointer items-center gap-3 rounded px-2 py-2 font-body text-sm text-on-surface hover:bg-surface-container" data-filter-multi-row data-search-text="{{ $option['searchText'] }}">
                    <x-atoms.checkbox name="{{ $name }}[]" id="{{ $id }}-{{ $option['value'] }}" value="{{ $option['value'] }}" :checked="$checked" data-table-filter />
                    <span>{{ $option['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>
</details>
