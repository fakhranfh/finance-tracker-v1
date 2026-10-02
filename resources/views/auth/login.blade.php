@extends('layouts.app')

@section('title', __('auth.login.title'))

@section('container-class', 'mx-auto max-w-[28rem] px-4 py-16')

@section('content')
    <x-templates.auth-card :title="__('auth.login.title')" :subtitle="__('auth.login.subtitle')">
        <x-organisms.login-form />
    </x-templates.auth-card>
@endsection

@push('scripts')
    @vite('resources/js/auth/login.js')
@endpush
