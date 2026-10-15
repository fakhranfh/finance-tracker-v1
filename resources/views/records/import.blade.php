@extends('layouts.dashboard')

@section('title', __('records.import.title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('records.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
            &larr; {{ __('records.import.back') }}
        </a>
        <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
            {{ __('records.import.title') }}
        </h1>
        <p class="mt-1 font-body text-body-md text-on-surface-variant">
            {{ __('records.import.subtitle') }}
        </p>
    </div>

    <div id="record-import" class="space-y-4" data-labels="{{ json_encode($labels) }}">
        <p id="import-step-label" class="font-body text-body-sm font-medium text-on-surface-variant"></p>

        <x-organisms.record-import-skeleton id="import-skeleton" class="hidden" />

        <section id="import-step-upload" class="space-y-6 rounded-lg border border-outline-variant bg-surface p-6">
            <div id="import-progress" class="hidden space-y-2">
                <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('records.import.resume_title') }}</h2>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.import.resume_hint') }}</p>
                <ul id="import-progress-list" class="divide-y divide-outline-variant"></ul>
            </div>

            <form id="import-upload-form" class="space-y-5" novalidate>
                @csrf

                <div class="space-y-1.5">
                    <x-atoms.label for="file">{{ __('records.import.file') }}</x-atoms.label>
                    <x-atoms.input type="file" name="file" accept=".xlsx,.xls,.csv" required />
                    <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.import.file_hint') }}</p>
                    <x-atoms.form-error for="file" />
                </div>

                <x-atoms.button type="submit">
                    {{ __('records.import.upload') }}
                </x-atoms.button>
            </form>
        </section>

        <section id="import-step-columns" class="hidden rounded-lg border border-outline-variant bg-surface p-6">
            <form id="import-columns-form" class="space-y-5" novalidate>
                <p class="font-body text-body-md text-on-surface-variant">{{ __('records.import.columns_hint') }}</p>

                @foreach ($fields as $field)
                    <div class="space-y-1.5">
                        <x-atoms.label for="mapping.{{ $field['key'] }}">
                            {{ $field['label'] }}@if ($field['required']) <span class="text-error">*</span>@endif
                        </x-atoms.label>
                        <x-atoms.select
                            name="mapping.{{ $field['key'] }}"
                            :required="$field['required']"
                            data-import-field="{{ $field['key'] }}"
                        >
                            <option value="">{{ __('records.import.not_mapped') }}</option>
                        </x-atoms.select>
                        @if ($field['hint'])
                            <p class="font-body text-body-sm text-on-surface-variant">{{ $field['hint'] }}</p>
                        @endif
                        <p class="font-body text-body-sm text-outline" data-import-example="{{ $field['key'] }}"></p>
                        <x-atoms.form-error for="mapping.{{ $field['key'] }}" />
                    </div>
                @endforeach

                <x-atoms.form-error for="import" />

                <div class="flex items-center gap-3">
                    <x-atoms.button type="submit">
                        {{ __('records.import.continue') }}
                    </x-atoms.button>
                    <x-atoms.button type="button" variant="secondary" data-import-back="upload">
                        {{ __('records.import.back_step') }}
                    </x-atoms.button>
                </div>
            </form>
        </section>

        <section id="import-step-values" class="hidden rounded-lg border border-outline-variant bg-surface p-6">
            <form id="import-values-form" class="space-y-8" novalidate>
                <div class="space-y-3">
                    <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('records.import.types_title') }}</h2>
                    <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.import.types_hint') }}</p>
                    <p id="import-types-empty" class="hidden rounded-lg border border-warning/20 bg-warning/10 px-4 py-3 font-body text-sm text-on-surface">
                        {{ __('records.import.types_empty') }}
                    </p>
                    <div id="import-types-fields" class="space-y-3">
                        <p id="import-types-found" class="font-body text-body-sm text-on-surface-variant"></p>
                        @foreach ($typeLabels as $value => $label)
                            <div class="grid grid-cols-1 items-center gap-2 sm:grid-cols-2 sm:gap-4">
                                <x-atoms.label for="import-type-{{ $value }}">{{ $label }}</x-atoms.label>
                                <x-atoms.input
                                    name="import_type_{{ $value }}"
                                    id="import-type-{{ $value }}"
                                    data-import-type="{{ $value }}"
                                    placeholder="{{ __('records.import.types_placeholder') }}"
                                    autocomplete="off"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('records.import.accounts_title') }}</h2>
                    <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.import.accounts_hint') }}</p>
                    <p id="import-accounts-empty" class="hidden rounded-lg border border-warning/20 bg-warning/10 px-4 py-3 font-body text-sm text-on-surface">
                        {{ __('records.import.accounts_empty') }}
                    </p>
                    <div id="import-accounts-list" class="space-y-3" data-import-list="account"></div>
                </div>

                <div class="space-y-3">
                    <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('records.import.categories_title') }}</h2>
                    <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.import.categories_hint') }}</p>
                    <p id="import-categories-empty" class="hidden rounded-lg border border-warning/20 bg-warning/10 px-4 py-3 font-body text-sm text-on-surface">
                        {{ __('records.import.categories_empty') }}
                    </p>
                    <div id="import-categories-list" class="space-y-3" data-import-list="category"></div>
                </div>

                <x-atoms.form-error for="import" />

                <div class="flex items-center gap-3">
                    <x-atoms.button type="submit">
                        {{ __('records.import.import') }}
                    </x-atoms.button>
                    <x-atoms.button type="button" variant="secondary" data-import-back="columns">
                        {{ __('records.import.back_step') }}
                    </x-atoms.button>
                </div>
            </form>
        </section>

        <section id="import-step-result" class="hidden space-y-4 rounded-lg border border-outline-variant bg-surface p-6">
            <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('records.import.result_title') }}</h2>
            <p id="import-result-imported" class="rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></p>
            <p id="import-result-accounts" class="hidden font-body text-body-md text-on-surface"></p>
            <p id="import-result-categories" class="hidden font-body text-body-md text-on-surface"></p>
            <div id="import-result-skipped" class="hidden space-y-2">
                <p id="import-result-skipped-title" class="font-body text-body-md font-medium text-on-surface"></p>
                <ul id="import-result-errors" class="list-disc space-y-1 pl-5 font-body text-body-sm text-on-surface-variant"></ul>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-atoms.button href="{{ route('records.index') }}">
                    {{ __('records.import.view_records') }}
                </x-atoms.button>
                <x-atoms.button type="button" variant="secondary" data-import-back="upload">
                    {{ __('records.import.import_another') }}
                </x-atoms.button>
            </div>
        </section>
    </div>

    <x-organisms.modal id="discard-import-modal" :title="__('records.import.discard_confirm')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('records.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-discard-import" variant="danger">
                {{ __('records.import.discard') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/records/import.js')
@endpush
