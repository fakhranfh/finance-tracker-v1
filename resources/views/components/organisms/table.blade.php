@props([
    'headers' => [],
    'selectable' => true,
    'config' => [],
])

@php
    $labels = $config['labels'] ?? [];
    $bulkDeleteLabel = $labels['bulkDelete'] ?? 'Delete selected';
    $selectedLabel = $labels['selected'] ?? 'selected';
    $emptyLabel = $labels['empty'] ?? 'No data';
    $dataAttributes = [
        'data-label-select-all-matching' => $labels['selectAllMatching'] ?? '',
        'data-label-all-selected' => $labels['allSelected'] ?? '',
        'data-label-clear-selection' => $labels['clearSelection'] ?? '',
        'data-label-expand' => $labels['expand'] ?? '',
        'data-label-collapse' => $labels['collapse'] ?? '',
        'data-label-edit' => $labels['edit'] ?? '',
        'data-label-delete' => $labels['delete'] ?? '',
        'data-locked-id' => $config['lockedId'] ?? '',
    ];
    $columnCount = count($headers) + ($selectable ? 1 : 0);
@endphp

@if ($selectable)
    <div data-table-select-all-mobile-wrap class="sticky top-16 z-20 bg-background py-3 sm:hidden">
        <x-atoms.button
            type="button"
            variant="secondary"
            class="h-11 w-full"
            data-table-select-all-mobile
            data-label-select="{{ __('common.table.select_all') }}"
            data-label-deselect="{{ __('common.table.deselect_all') }}"
            aria-pressed="false"
        >
            {{ __('common.table.select_all') }}
        </x-atoms.button>
    </div>
@endif

<div class="overflow-x-auto rounded-lg border border-outline-variant bg-surface max-sm:overflow-visible max-sm:border-0 max-sm:bg-transparent">
    <table class="w-full text-left font-body text-body-md max-sm:block">
        <thead class="max-sm:hidden border-b border-outline-variant bg-surface-container text-body-sm text-on-surface-variant">
            <tr>
                @if ($selectable)
                    <th class="w-10 px-4 py-3">
                        <x-atoms.checkbox data-table-select-all aria-label="Select all" />
                    </th>
                @endif

                @foreach ($headers as $header)
                    @php
                        $isSortable = is_array($header) && isset($header['sort']);
                        $label = is_array($header) ? $header['label'] : $header;
                    @endphp

                    <th class="px-4 py-3 font-medium">
                        @if ($isSortable)
                            <button
                                type="button"
                                data-sort-key="{{ $header['sort'] }}"
                                class="inline-flex cursor-pointer items-center gap-1 font-medium text-on-surface-variant hover:text-on-surface"
                            >
                                {{ $label }}
                                <svg data-sort-icon="{{ $header['sort'] }}" class="invisible h-3.5 w-3.5 text-primary" data-direction="" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @else
                            {{ $label }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody {{ $attributes->merge($dataAttributes)->merge(['class' => 'divide-y divide-outline-variant max-sm:block max-sm:space-y-3 max-sm:divide-y-0 max-sm:[&>tr:not(.hidden)]:block max-sm:[&>tr:not([data-table-bulk-row]):not([data-table-empty-row])]:rounded-lg max-sm:[&>tr:not([data-table-bulk-row]):not([data-table-empty-row])]:border max-sm:[&>tr:not([data-table-bulk-row]):not([data-table-empty-row])]:border-outline-variant max-sm:[&>tr:not([data-table-bulk-row]):not([data-table-empty-row])]:bg-surface max-sm:[&>tr:not([data-table-bulk-row]):not([data-table-empty-row])]:py-1 max-sm:[&>tr:not([data-table-bulk-row])]:relative max-sm:[&>tr]:overflow-hidden max-sm:[&_td]:block max-sm:[&_td[data-label]]:flex max-sm:[&_td[data-label]]:items-center max-sm:[&_td[data-label]]:justify-between max-sm:[&_td[data-label]]:gap-4 max-sm:[&_td[data-label]]:before:text-body-sm max-sm:[&_td[data-label]]:before:font-medium max-sm:[&_td[data-label]]:before:text-on-surface-variant max-sm:[&_td[data-label]]:before:content-[attr(data-label)] max-sm:[&_td[data-label]]:py-2 max-sm:[&_td]:w-auto max-sm:[&_tr:not([data-expanded])_td[data-collapsible]]:hidden']) }}>
            @if ($selectable)
                <tr data-table-bulk-row class="hidden bg-surface-container max-sm:sticky max-sm:top-16 max-sm:z-10 max-sm:-mx-4">
                    <td colspan="{{ $columnCount }}" class="px-4 py-2.5">
                        <div class="sticky left-0 flex w-fit max-w-[calc(100vw-4rem)] max-sm:w-full max-sm:max-w-none flex-wrap items-center gap-3 font-body sm:w-full sm:max-w-none text-body-sm text-on-surface-variant">
                            <span><span data-table-selected-count>0</span> {{ $selectedLabel }}</span>

                            <button type="button" data-table-select-all-matching class="hidden cursor-pointer font-medium text-primary hover:underline"></button>
                            <button type="button" data-table-clear-selection class="hidden cursor-pointer font-medium text-primary hover:underline"></button>

                            <div class="ml-auto flex items-center gap-2">
                                <x-atoms.button
                                    type="button"
                                    variant="secondary"
                                    class="sm:hidden"
                                    data-table-select-all-mobile
                                    data-label-select="{{ __('common.table.select_all') }}"
                                    data-label-deselect="{{ __('common.table.deselect_all') }}"
                                    aria-pressed="false"
                                >
                                    {{ __('common.table.select_all') }}
                                </x-atoms.button>

                                <x-atoms.button
                                    type="button"
                                    variant="danger"
                                    data-table-bulk-delete
                                >
                                    {{ $bulkDeleteLabel }}
                                </x-atoms.button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endif

            <tr data-table-empty-row class="hidden">
                <td colspan="{{ $columnCount }}" class="px-4 py-6 text-center text-on-surface-variant">
                    {{ $emptyLabel }}
                </td>
            </tr>

            {{ $slot }}
        </tbody>
    </table>
</div>
