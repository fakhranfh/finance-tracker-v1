@extends('layouts.dashboard')

@section('content')
    <x-organisms.account-cards />

    <div class="mt-4 flex justify-end">
        <x-atoms.button href="{{ route('records.index') }}" variant="secondary">
            {{ __('dashboard.nav.records') }}
        </x-atoms.button>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/account-cards/index.js')
@endpush
