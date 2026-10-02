<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', __('dashboard.title') . ' - ' . config('app.name', 'Laravel'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    </head>
    <body class="bg-background font-body text-on-surface antialiased">
        <div class="flex min-h-screen">
            <x-organisms.sidebar />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                <x-organisms.topbar />

                <main class="flex-1 p-4 sm:p-6">
                    @if (session('success'))
                        <div class="mb-6 rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <div data-error-snackbar role="alert" class="fixed inset-x-4 top-20 z-50 hidden max-w-sm rounded-lg border border-error/20 bg-error-container p-4 font-body text-body-sm text-on-error-container shadow-lg sm:left-auto sm:right-4">
            <p class="mb-2 font-semibold">{{ __('common.validation_summary_heading') }}</p>
            <div data-error-snackbar-list class="space-y-1 [&>button]:block [&>button]:w-full [&>button]:cursor-pointer [&>button]:text-left [&>button]:underline"></div>
        </div>

        @stack('scripts')
    </body>
</html>
