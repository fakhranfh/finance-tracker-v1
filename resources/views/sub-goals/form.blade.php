@extends('layouts.dashboard')

@section('title', __('sub-goals.edit'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.sub-goals.index', $goalId) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-goals.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-goals.edit') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-goal-name></p>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('sub-goals.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.sub-goal-form-skeleton id="sub-goal-form-skeleton" />

        <form
            id="sub-goal-form"
            class="hidden space-y-5"
            novalidate
            data-goal-id="{{ $goalId }}"
            data-locked-message="{{ __('goals.locked') }}"
            data-success-message="{{ __('sub-goals.flash.updated') }}"
        >
            @csrf

            <input type="hidden" name="target_amount">
            <input type="hidden" name="saved_amount">
            <input type="hidden" name="desired_date">

            <x-organisms.sub-goals-field />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('sub-goals.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('goals.sub-goals.index', $goalId) }}" variant="secondary">
                    {{ __('sub-goals.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>

    <x-organisms.calculator-modal />
@endsection

@push('scripts')
    @vite('resources/js/sub-goals/form.js')
@endpush
