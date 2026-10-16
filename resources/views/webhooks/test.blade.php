@extends('layouts.dashboard')

@section('title', __('webhooks.test.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ $backUrl }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ $backLabel }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('webhooks.test.title') }}
            </h1>
        </div>

        <x-atoms.button href="{{ route('webhooks.logs', ['id' => $id, 'from' => $from]) }}" variant="secondary">
            {{ __('webhooks.logs.title') }}
        </x-atoms.button>
    </div>

    <x-organisms.webhook-guide />

    <x-organisms.webhook-tester :id="$id" />
@endsection

@push('scripts')
    @vite('resources/js/webhooks/test.js')
@endpush
