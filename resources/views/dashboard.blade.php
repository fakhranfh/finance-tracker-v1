@extends('layouts.dashboard')

@section('content')
    <x-organisms.account-cards />
@endsection

@push('scripts')
    @vite('resources/js/account-cards/index.js')
@endpush
