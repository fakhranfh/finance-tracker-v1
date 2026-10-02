@extends('layouts.dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="font-heading text-headline-md font-bold text-on-surface">
            {{ __('dashboard.welcome.title', ['name' => auth()->user()->name]) }}
        </h1>
        <p class="mt-1 font-body text-body-md text-on-surface-variant">
            {{ __('dashboard.welcome.subtitle') }}
        </p>
    </div>

    <section class="max-w-md rounded-xl border border-outline-variant bg-surface p-5">
        <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('dashboard.start.title') }}</h2>
        <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ __('dashboard.start.users_description') }}</p>
        <a href="{{ route('users.index') }}" class="mt-4 inline-block rounded-lg bg-primary px-4 py-2 font-body text-body-md font-semibold text-on-primary">
            {{ __('dashboard.start.users_button') }}
        </a>
    </section>
@endsection
