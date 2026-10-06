@extends('layouts.dashboard')

@section('title', $account->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('accounts.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
            &larr; {{ __('accounts.back') }}
        </a>
        <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">{{ $account->name }}</h1>
        <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ __('accounts.summary.subtitle') }}</p>
    </div>

    <div class="max-sm:pb-20" id="account-summary" data-account-id="{{ $account->id }}" data-records-label="{{ __('accounts.summary.records', ['count' => ':count']) }}">
        <div data-summary-filter-inline class="mb-6 hidden sm:block">
            <form id="account-summary-filter" method="GET" action="{{ route('accounts.summary', $account) }}" class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                @include('accounts.partials.summary-filter-fields', ['presets' => $presets, 'range' => $range])
            </form>
        </div>

        <div id="account-summary-skeleton" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-atoms.skeleton class="h-28 rounded-lg" />
            <x-atoms.skeleton class="h-28 rounded-lg" />
            <x-atoms.skeleton class="h-28 rounded-lg" />
        </div>

        <div id="account-summary-results">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.income') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-success" data-summary-value="income">…</p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.expense') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-error" data-summary-value="expense">…</p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.transfer') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface">
                    {{ __('accounts.summary.transfer_in') }}: <span data-summary-value="transfer_in">…</span>
                </p>
                <p class="mt-1 font-body text-body-sm text-on-surface-variant">
                    {{ __('accounts.summary.transfer_out') }}: <span data-summary-value="transfer_out">…</span>
                </p>
            </div>
        </div>

        <p class="mt-4 font-body text-body-sm text-on-surface-variant">
            {{ __('accounts.summary.net') }}: <span data-summary-value="net">…</span>
            &middot; <span data-summary-value="record_count">…</span>
        </p>
        </div>
    </div>

    {{-- Mobile: same bottom bar as the table; the filter form moves into this modal on small screens. --}}
    <div class="fixed inset-x-0 bottom-0 z-40 flex gap-3 border-t border-outline-variant bg-surface p-3 sm:hidden">
        <x-atoms.button type="button" variant="secondary" class="flex-1" data-modal-open="account-summary-filter-modal">
            {{ __('common.table.filter') }}
        </x-atoms.button>
    </div>

    <x-organisms.modal id="account-summary-filter-modal" :title="__('common.table.filter')">
        <div data-summary-filter-slot></div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/accounts/summary.js')
@endpush
