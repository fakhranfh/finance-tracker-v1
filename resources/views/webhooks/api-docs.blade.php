@extends('layouts.dashboard')

@section('title', __('webhooks.api_docs.title'))

@php
    // Swagger UI's method colours.
    $methodColors = [
        'GET' => '#61affe',
        'POST' => '#49cc90',
        'PUT' => '#fca130',
        'PATCH' => '#50e3c2',
        'DELETE' => '#f93e3e',
    ];
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('webhooks.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
            &larr; {{ __('webhooks.title') }}
        </a>
        <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
            {{ __('webhooks.api_docs.title') }}
        </h1>
        <p class="mt-1 font-body text-body-md text-on-surface-variant">
            {{ __('webhooks.api_docs.subtitle') }}
        </p>
    </div>

    @if (empty($operations))
        <p class="rounded-lg border border-outline-variant bg-surface p-6 font-body text-body-md text-on-surface-variant">
            {{ __('webhooks.api_docs.empty') }}
        </p>
    @endif

    <div class="space-y-3" id="webhook-api-operations">
        @foreach ($operations as $operation)
            @php $color = $methodColors[$operation['method']] ?? '#808080'; @endphp

            <details id="{{ $operation['id'] }}" class="scroll-mt-24 overflow-hidden rounded-md border" style="border-color: {{ $color }}; background: color-mix(in srgb, {{ $color }} 8%, transparent);">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 px-4 py-3">
                    <span class="min-w-[4.5rem] rounded px-2 py-1 text-center font-mono text-xs font-bold text-white" style="background: {{ $color }};">
                        {{ $operation['method'] }}
                    </span>
                    <code class="font-mono text-body-md font-semibold text-on-surface">{{ $operation['path'] }}</code>
                    <span class="font-body text-body-sm text-on-surface-variant">{{ $operation['summary'] }}</span>
                </summary>

                <div class="space-y-5 border-t border-outline-variant bg-surface px-4 py-4">
                    @if ($operation['description'] !== '')
                        <p class="font-body text-body-sm text-on-surface-variant">{{ $operation['description'] }}</p>
                    @endif

                    <section
                        class="space-y-3 rounded-md border border-outline-variant p-4"
                        data-try-it-out
                        data-required-message="{{ __('webhooks.api_docs.field_required') }}"
                        data-integer-message="{{ __('webhooks.api_docs.field_integer') }}"
                    >
                        <div class="flex justify-end">
                            <x-atoms.button
                                type="button"
                                variant="secondary"
                                data-try-toggle
                                data-open-label="{{ __('webhooks.api_docs.try_it_out') }}"
                                data-close-label="{{ __('webhooks.api_docs.cancel') }}"
                            >
                                {{ __('webhooks.api_docs.try_it_out') }}
                            </x-atoms.button>
                        </div>

                        <div data-try-panel class="hidden space-y-4">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <x-atoms.label for="try-webhook-id">{{ __('webhooks.api_docs.webhook_id') }} <span class="text-error">*</span></x-atoms.label>
                                    <x-atoms.select name="try-webhook-id" id="try-webhook-id" data-try-id>
                                        <option value="">{{ __('webhooks.api_docs.select_webhook') }}</option>
                                        @foreach ($webhooks as $webhook)
                                            <option value="{{ $webhook->id }}">{{ $webhook->name }} (#{{ $webhook->id }})</option>
                                        @endforeach
                                    </x-atoms.select>
                                    <x-atoms.form-error for="try-webhook-id" />
                                </div>
                                <div>
                                    <x-atoms.label for="try-timestamp">X-Webhook-Timestamp</x-atoms.label>
                                    <x-atoms.input type="number" name="try-timestamp" id="try-timestamp" data-try-timestamp placeholder="{{ __('webhooks.api_docs.auto') }}" />
                                </div>
                                <div>
                                    <x-atoms.label for="try-nonce">X-Webhook-Nonce</x-atoms.label>
                                    <x-atoms.input type="text" name="try-nonce" id="try-nonce" data-try-nonce placeholder="{{ __('webhooks.api_docs.auto') }}" />
                                </div>
                            </div>

                            <div class="space-y-4">
                                <p class="font-body text-body-sm font-semibold text-on-surface">{{ __('webhooks.api_docs.request_body') }}</p>

                                <div class="space-y-2">
                                    <x-atoms.label>{{ __('webhooks.api_docs.type') }}</x-atoms.label>
                                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                                        <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                                            <x-atoms.radio name="try-type" id="try-type-expense" value="expense" data-try-type checked />
                                            expense
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                                            <x-atoms.radio name="try-type" id="try-type-income" value="income" data-try-type />
                                            income
                                        </label>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-atoms.label for="try-account-name">{{ __('webhooks.api_docs.account_name') }}</x-atoms.label>
                                        <x-atoms.input type="text" name="try-account-name" id="try-account-name" data-try-account />
                                        <x-atoms.form-error for="try-account-name" />
                                    </div>
                                    <div>
                                        <x-atoms.label for="try-amount">{{ __('webhooks.api_docs.amount') }}</x-atoms.label>
                                        <x-atoms.input type="number" name="try-amount" id="try-amount" step="1" min="0" value="12" data-try-amount />
                                        <x-atoms.form-error for="try-amount" />
                                    </div>
                                    <div>
                                        <x-atoms.label for="try-note">{{ __('webhooks.api_docs.note') }}</x-atoms.label>
                                        <x-atoms.input type="text" name="try-note" id="try-note" value="Webhook test" data-try-note />
                                    </div>
                                    <div>
                                        <x-atoms.label for="try-recorded-at">{{ __('webhooks.api_docs.recorded_at') }}</x-atoms.label>
                                        <x-atoms.input type="datetime-local" name="try-recorded-at" id="try-recorded-at" data-try-recorded-at />
                                        <x-atoms.form-error for="try-recorded-at" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-atoms.label for="try-ip">IP <span class="font-normal text-on-surface-variant">({{ __('webhooks.api_docs.optional') }})</span></x-atoms.label>
                                        <x-atoms.input type="text" name="try-ip" id="try-ip" placeholder="203.0.113.5" data-try-ip />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-atoms.button type="button" data-try-execute>{{ __('webhooks.api_docs.execute') }}</x-atoms.button>
                                <x-atoms.button type="button" variant="secondary" data-try-cancel>{{ __('webhooks.api_docs.cancel') }}</x-atoms.button>
                            </div>

                            <div data-try-response class="hidden space-y-2">
                                <p class="font-body text-body-sm text-on-surface-variant">
                                    {{ __('webhooks.api_docs.request_url') }}: <code class="font-mono break-all text-on-surface" data-try-url></code>
                                </p>
                                <p class="font-body text-body-sm text-on-surface-variant">
                                    {{ __('webhooks.api_docs.server_response') }}: <span class="font-mono font-semibold text-on-surface" data-try-code></span>
                                </p>
                                <pre class="overflow-x-auto rounded-md bg-surface-container p-3 font-mono text-body-sm text-on-surface" data-try-result></pre>
                            </div>
                        </div>
                    </section>

                    @if (! empty($operation['parameters']))
                        <section>
                            <h3 class="mb-2 font-heading text-body-md font-semibold text-on-surface">{{ __('webhooks.api_docs.parameters') }}</h3>
                            <ul class="space-y-2">
                                @foreach ($operation['parameters'] as $parameter)
                                    <li class="font-body text-body-sm">
                                        <code class="font-mono font-semibold text-on-surface">{{ $parameter['name'] }}</code>
                                        <span class="text-on-surface-variant">({{ $parameter['in'] }}, {{ $parameter['type'] }})</span>
                                        @if ($parameter['required'])
                                            <span class="font-semibold text-error">{{ __('webhooks.api_docs.required') }}</span>
                                        @endif
                                        @if ($parameter['description'] !== '')
                                            <p class="text-on-surface-variant">{{ $parameter['description'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($operation['body']))
                        <section>
                            <h3 class="mb-2 font-heading text-body-md font-semibold text-on-surface">{{ __('webhooks.api_docs.request_body') }}</h3>
                            <ul class="space-y-2">
                                @foreach ($operation['body'] as $property)
                                    <li class="font-body text-body-sm">
                                        <code class="font-mono font-semibold text-on-surface">{{ $property['name'] }}</code>
                                        <span class="text-on-surface-variant">({{ $property['type'] }})</span>
                                        @if ($property['required'])
                                            <span class="font-semibold text-error">{{ __('webhooks.api_docs.required') }}</span>
                                        @endif
                                        @if ($property['description'] !== '')
                                            <p class="text-on-surface-variant">{{ $property['description'] }}</p>
                                        @endif
                                        @if ($property['example'] !== null)
                                            <p class="text-on-surface-variant">{{ __('webhooks.api_docs.example') }}: <code class="font-mono">{{ is_bool($property['example']) ? ($property['example'] ? 'true' : 'false') : $property['example'] }}</code></p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($operation['responses']))
                        <section>
                            <h3 class="mb-2 font-heading text-body-md font-semibold text-on-surface">{{ __('webhooks.api_docs.responses') }}</h3>
                            <ul class="space-y-1">
                                @foreach ($operation['responses'] as $response)
                                    <li class="font-body text-body-sm">
                                        <code class="font-mono font-semibold text-on-surface">{{ $response['code'] }}</code>
                                        <span class="text-on-surface-variant">{{ $response['description'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
@endsection

@push('scripts')
    @vite('resources/js/webhooks/api-docs.js')
@endpush
