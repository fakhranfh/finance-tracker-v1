@extends('layouts.dashboard')

@section('title', __('goals.show'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('goals.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('goals.show') }}
            </h1>
        </div>

        <x-atoms.button href="{{ route('goals.edit', $id) }}" variant="secondary">
            {{ __('goals.actions.edit') }}
        </x-atoms.button>
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.goal-show-skeleton id="goal-show-skeleton" />

        <div
            id="goal-show"
            class="hidden"
            data-item-id="{{ $id }}"
            data-icons="{{ json_encode($icons) }}"
            data-status-labels="{{ json_encode($statusLabels) }}"
        >
            <div class="mb-6 flex items-center gap-3">
                <span data-field="icon"></span>
                <h2 class="font-heading text-title-lg font-bold text-on-surface" data-field="name"></h2>
            </div>

            <div class="mb-6 space-y-2">
                <div class="flex justify-between font-body text-body-sm text-on-surface-variant">
                    <span>{{ __('goals.show_page.progress') }}</span>
                    <span data-field="progress_label"></span>
                </div>
                <progress class="h-2 w-full overflow-hidden rounded-full" max="100" value="0" data-field="progress"></progress>
            </div>

            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ($fields as $field)
                    <div @class(['sm:col-span-2' => $field === 'note'])>
                        <dt class="font-body text-body-sm text-on-surface-variant">{{ __("goals.show_page.{$field}") }}</dt>
                        <dd class="mt-1 font-body text-body-md text-on-surface" data-field="{{ $field }}"></dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <p id="goal-show-missing" class="hidden font-body text-body-md text-error">{{ __('goals.show_page.not_found') }}</p>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/goals/show.js')
@endpush
