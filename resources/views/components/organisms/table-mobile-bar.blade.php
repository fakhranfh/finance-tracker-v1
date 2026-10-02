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
    <div class="flex flex-col gap-4">
        <x-atoms.input
            type="search"
            name="search"
            data-modal-search
            placeholder="{{ $searchPlaceholder }}"
        />

        <x-molecules.per-page-select :label="$perPageLabel" class="justify-between" />

        <div class="flex justify-end">
            <x-atoms.button type="button" data-modal-close>{{ __('common.table.done') }}</x-atoms.button>
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
