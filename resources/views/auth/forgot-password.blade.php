@extends('layouts.app')

@section('title', __('auth.forgot_password.title'))

@section('container-class', 'mx-auto max-w-[28rem] px-4 py-16')

@section('content')
    <x-templates.auth-card :title="__('auth.forgot_password.title')" :subtitle="__('auth.forgot_password.subtitle')">
        <x-organisms.forgot-password-form />
    </x-templates.auth-card>
@endsection

@push('scripts')
    @vite('resources/js/auth/forgot-password.js')
@endpush
