@extends('layouts.dashboard')

@section('title', __('debt-payments.pages.'.$type.'.existing'))

@section('content')
    <div class="mb-6">
        <a href="{{ route($type === 'debt' ? 'debts.payments.index' : 'receivables.payments.index', $debtId) }}" id="back-to-payments" class="font-body text-body-sm text-on-surface-variant hover:underline">
            &larr; {{ __('debt-payments.pages.'.$type.'.back_to_payments') }}
        </a>
        <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
            {{ __('debt-payments.pages.'.$type.'.existing') }}
        </h1>
        <p class="mt-1 font-body text-body-md text-on-surface-variant" data-debt-name>
            {{ __('debt-payments.pages.'.$type.'.existing_hint') }}
        </p>
    </div>

    <div id="debt-existing-page" data-debt-id="{{ $debtId }}" data-type="{{ $type }}" data-used-message="{{ __('debt-payments.flash.used') }}"></div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
    <div data-use-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error"></div>

    <div class="mb-4 hidden max-w-sm space-y-1.5" data-sub-debt-group>
        <x-atoms.label for="sub_debt_id">{{ __('debt-payments.fields.sub_debt') }}</x-atoms.label>
        <x-atoms.select name="sub_debt_id" id="sub_debt_id">
            <option value="">{{ __('debt-payments.fields.select_sub_debt') }}</option>
        </x-atoms.select>
    </div>

    <x-molecules.table-toolbar
        id="debt-existing-table-toolbar"
        :search-placeholder="__('debt-payments.table.search')"
        :per-page-label="__('debt-payments.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="debt-existing-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('debt-payments.table.search')"
        :per-page-label="__('debt-payments.table.per_page')"
    />

    <div id="debt-existing-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="debt-existing-table"
            :selectable="false"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'empty' => __('debt-payments.existing.empty'),
                    'expand' => __('debt-payments.table.show_more'),
                    'collapse' => __('debt-payments.table.show_less'),
                    'edit' => __('debt-payments.actions.use'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>
@endsection

@push('scripts')
    @vite('resources/js/debt-payments/existing.js')
@endpush
