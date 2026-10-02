@props([
    'id',
    'searchPlaceholder' => 'Search...',
    'perPageLabel' => 'Items per page',
])

<div id="{{ $id }}" class="mb-4 flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-3 max-sm:hidden">
        <div class="min-w-64">
            <x-atoms.input
                type="search"
                name="search"
                data-table-search
                placeholder="{{ $searchPlaceholder }}"
            />
        </div>

        <x-molecules.per-page-select :label="$perPageLabel" />
    </div>

    <div class="flex justify-end">
        <x-molecules.pagination data-pagination class="!mt-0 max-sm:w-full max-sm:justify-end" />
    </div>
</div>
