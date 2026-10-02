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

@endsection
