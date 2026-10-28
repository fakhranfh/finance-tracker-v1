<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#004ac6">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/favicon.ico">
        <link rel="apple-touch-icon" href="/icons/icon-512.png">
        <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    </head>
    <body class="bg-background font-body text-on-surface antialiased">
        @section('body')
            <main class="@yield('container-class', 'mx-auto max-w-3xl px-4 py-10')">
                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>

            <div data-error-snackbar role="alert" class="fixed inset-x-4 top-4 z-50 hidden max-w-sm rounded-lg border border-error/20 bg-error-container p-4 font-body text-body-sm text-on-error-container shadow-lg sm:left-auto sm:right-4">
                <p class="mb-2 font-semibold">{{ __('common.validation_summary_heading') }}</p>
                <div data-error-snackbar-list class="space-y-1 [&>button]:block [&>button]:w-full [&>button]:cursor-pointer [&>button]:text-left [&>button]:underline"></div>
            </div>
        @show

        @stack('scripts')
    </body>
</html>
