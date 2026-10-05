@props([
    'id',
    'headers' => [],
    'searchPlaceholder' => 'Search...',
    'perPageLabel' => 'Items per page',
])

@php
    $sortable = collect($headers)->filter(fn ($header) => is_array($header) && isset($header['sort']));
@endphp

<div class="fixed inset-x-0 bottom-0 z-40 flex gap-3 border-t border-outline-variant bg-surface p-3 sm:hidden">
    <x-atoms.button type="button" variant="secondary" class="flex-1" data-modal-open="{{ $id }}-filter">
        {{ __('common.table.filter') }}
    </x-atoms.button>
    <x-atoms.button type="button" variant="secondary" class="flex-1" data-modal-open="{{ $id }}-sort">
        {{ __('common.table.sort') }}
    </x-atoms.button>
</div>

<x-organisms.modal id="{{ $id }}-filter" :title="__('common.table.filter')">
    <div class="flex max-h-[70dvh] flex-col">
        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto pb-4 pr-1">
            <x-atoms.input
                type="search"
                name="search"
                data-modal-search
                placeholder="{{ $searchPlaceholder }}"
            />

            <x-molecules.per-page-select :label="$perPageLabel" class="justify-between" />

            {{-- Table filters are moved here on mobile by shared/table.js --}}
            <div data-table-filters-slot class="flex flex-col"></div>
        </div>

        <div class="grid grid-cols-2 gap-3 border-t border-outline-variant pt-4">
            {{-- Clear-filters button is moved here on mobile by shared/table.js --}}
            <div data-table-filters-footer-slot class="min-w-0 [&>*]:w-full [&_button]:w-full"></div>
            <x-atoms.button type="button" class="w-full" data-modal-close>
                <svg class="h-5 w-5 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span class="max-sm:sr-only">{{ __('common.table.done') }}</span>
            </x-atoms.button>
        </div>
    </div>
</x-organisms.modal>

<x-organisms.modal id="{{ $id }}-sort" :title="__('common.table.sort')">
    <div class="flex flex-col gap-4 font-body text-body-md text-on-surface">
        <label class="flex items-center gap-2">
            <input type="radio" name="{{ $id }}-sort-key" value="" data-modal-sort-key checked>
            {{ __('common.table.sort_none') }}
        </label>
        @foreach ($sortable as $header)
            <label class="flex items-center gap-2">
                <input type="radio" name="{{ $id }}-sort-key" value="{{ $header['sort'] }}" data-modal-sort-key>
                {{ $header['label'] }}
            </label>
        @endforeach

        <div class="flex gap-4 border-t border-outline-variant pt-4">
            <label class="flex items-center gap-2">
                <input type="radio" name="{{ $id }}-sort-direction" value="asc" data-modal-sort-direction checked>
                {{ __('common.table.ascending') }}
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="{{ $id }}-sort-direction" value="desc" data-modal-sort-direction>
                {{ __('common.table.descending') }}
            </label>
        </div>

        <div class="flex justify-end">
            <x-atoms.button type="button" data-modal-close>{{ __('common.table.done') }}</x-atoms.button>
        </div>
    </div>
</x-organisms.modal>
