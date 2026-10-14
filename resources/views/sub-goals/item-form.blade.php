@extends('layouts.dashboard')

@section('title', __('sub-goals.edit_one'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.sub-goals.show', [$goalId, $id]) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-goals.back_to_sub_goal') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-goals.edit_one') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('sub-goals.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.sub-goal-item-form-skeleton id="sub-goal-item-form-skeleton" />

        <form
            id="sub-goal-item-form"
            class="hidden space-y-5"
            novalidate
            data-goal-id="{{ $goalId }}"
            data-item-id="{{ $id }}"
            data-locked-message="{{ __('goals.locked') }}"
            data-success-message="{{ __('sub-goals.flash.updated_one') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('sub-goals.fields.name')" maxlength="255" required autofocus />

            <div class="space-y-1.5">
                <x-atoms.label for="target_amount">{{ __('sub-goals.fields.target_amount') }}</x-atoms.label>
                <x-atoms.input type="text" name="target_amount" id="target_amount" inputmode="decimal" data-money readonly class="cursor-not-allowed bg-surface-container-low" />
                <p class="font-body text-body-sm text-on-surface-variant">
                    {{ __('sub-goals.item_form.amount_hint') }}
                    <a href="{{ route('goals.sub-goals.edit', $goalId) }}" class="text-primary hover:underline">{{ __('sub-goals.form.edit') }}</a>
                </p>
            </div>

            <div class="space-y-1.5">
                <x-atoms.label for="saved_amount">{{ __('sub-goals.fields.saved_amount') }}</x-atoms.label>
                <x-atoms.input type="text" name="saved_amount" id="saved_amount" inputmode="decimal" data-money />
            </div>

            <x-molecules.form-group name="desired_date" type="date" :label="__('sub-goals.fields.desired_date')" />
            <div class="flex items-center gap-2">
                <x-atoms.checkbox name="is_done" id="is_done" />
                <x-atoms.label for="is_done">{{ __('sub-goals.fields.is_done') }}</x-atoms.label>
            </div>
            <x-molecules.form-group name="note" type="text" :label="__('sub-goals.fields.note')" maxlength="1000" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('sub-goals.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('goals.sub-goals.show', [$goalId, $id]) }}" variant="secondary">
                    {{ __('sub-goals.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/sub-goals/item-form.js')
@endpush
