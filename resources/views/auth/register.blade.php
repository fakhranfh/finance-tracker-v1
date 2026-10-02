@extends('layouts.app')

@section('title', __('auth.register.title'))

@section('container-class', 'mx-auto max-w-[28rem] px-4 py-16')

@section('content')
    <x-templates.auth-card :title="__('auth.register.title')" :subtitle="__('auth.register.subtitle')">
        <x-organisms.register-form />
    </x-templates.auth-card>
@endsection

@push('scripts')
    @vite('resources/js/auth/register.js')
@endpush
