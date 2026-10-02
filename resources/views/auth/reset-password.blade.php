@extends('layouts.app')

@section('title', __('auth.reset_password.title'))

@section('container-class', 'mx-auto max-w-[28rem] px-4 py-16')

@section('content')
    <x-templates.auth-card :title="__('auth.reset_password.title')" :subtitle="__('auth.reset_password.subtitle')">
        <x-organisms.reset-password-form :token="$token" :email="request()->query('email')" />
    </x-templates.auth-card>
@endsection

@push('scripts')
    @vite('resources/js/auth/reset-password.js')
@endpush
