@extends('layouts.app')

@section('title', __('auth.verify_email.title'))

@section('container-class', 'mx-auto max-w-[28rem] px-4 py-16')

@section('content')
    <x-templates.auth-card :title="__('auth.verify_email.title')" :subtitle="__('auth.verify_email.subtitle', ['email' => $email])">
        <div id="verify-email"
             class="space-y-5"
             data-cooldown="{{ $cooldown }}"
             data-label-send="{{ __('auth.verify_email.send') }}"
             data-label-resend="{{ __('auth.verify_email.resend_in') }}">
            <div data-form-success class="hidden rounded-md border border-success/20 bg-success/10 px-4 py-3 font-body text-body-sm text-success"></div>
            <div data-form-error class="hidden rounded-md border border-error/20 bg-error/10 px-4 py-3 font-body text-body-sm text-error"></div>

            <x-atoms.button type="button" class="w-full" data-verify-send>
                {{ __('auth.verify_email.send') }}
            </x-atoms.button>

            <p class="text-center font-body text-body-sm text-on-surface-variant">
                <button type="button" data-logout-trigger class="font-medium text-primary hover:underline">
                    {{ __('auth.verify_email.logout') }}
                </button>
            </p>
        </div>
    </x-templates.auth-card>
@endsection

@push('scripts')
    @vite('resources/js/auth/verify-email.js')
@endpush
