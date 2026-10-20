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
            @php
                $color = $methodColors[$operation['method']] ?? '#808080';
                // Spec parameters and body properties, by name, for the hint under each field.
                $docs = collect($operation['parameters'])->concat($operation['body'])->keyBy('name');
            @endphp

            <div id="{{ $operation['id'] }}" class="scroll-mt-24 overflow-hidden rounded-md border" style="border-color: {{ $color }}; background: color-mix(in srgb, {{ $color }} 8%, transparent);">
                <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <span class="min-w-[4.5rem] rounded px-2 py-1 text-center font-mono text-xs font-bold text-white" style="background: {{ $color }};">
                        {{ $operation['method'] }}
                    </span>
                    <code class="font-mono text-body-md font-semibold text-on-surface">{{ $operation['path'] }}</code>
                    <span class="font-body text-body-sm text-on-surface-variant">{{ $operation['summary'] }}</span>
                </div>

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
                        <div class="space-y-4">
                            <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.api_docs.headers_help') }}</p>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <x-atoms.label class="mb-1.5" for="try-webhook-id">{{ __('webhooks.api_docs.webhook_id') }} <span class="text-error">*</span></x-atoms.label>
                                    <x-atoms.select name="try-webhook-id" id="try-webhook-id" data-try-id>
                                        <option value="">{{ __('webhooks.api_docs.select_webhook') }}</option>
                                        @foreach ($webhooks as $webhook)
                                            <option value="{{ $webhook->id }}" @selected($webhook->id === $selectedWebhookId)>{{ $webhook->name }} (#{{ $webhook->id }})</option>
                                        @endforeach
                                    </x-atoms.select>
                                    @include('webhooks.partials.field-doc', ['doc' => $docs['id'] ?? null])
                                    <x-atoms.form-error for="try-webhook-id" />
                                </div>
                                <div>
                                    <x-atoms.label class="mb-1.5" for="try-timestamp">X-Webhook-Timestamp</x-atoms.label>
                                    <x-atoms.input type="number" name="try-timestamp" id="try-timestamp" data-try-timestamp placeholder="{{ __('webhooks.api_docs.auto') }}" />
                                    @include('webhooks.partials.field-doc', ['doc' => $docs['X-Webhook-Timestamp'] ?? null])
                                </div>
                                <div>
                                    <x-atoms.label class="mb-1.5" for="try-nonce">X-Webhook-Nonce</x-atoms.label>
                                    <x-atoms.input type="text" name="try-nonce" id="try-nonce" data-try-nonce placeholder="{{ __('webhooks.api_docs.auto') }}" />
                                    @include('webhooks.partials.field-doc', ['doc' => $docs['X-Webhook-Nonce'] ?? null])
                                </div>
                            </div>

                            <div class="space-y-4">
                                <p class="font-body text-body-sm font-semibold text-on-surface">{{ __('webhooks.api_docs.request_body') }}</p>

                                <div class="space-y-2">
                                    <x-atoms.label class="mb-1.5">{{ __('webhooks.api_docs.type') }}</x-atoms.label>
                                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                                        @foreach ($types as $value => $label)
                                            <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                                                <x-atoms.radio name="try-type" id="try-type-{{ $value }}" value="{{ $value }}" data-try-type :checked="$loop->first" />
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @include('webhooks.partials.field-doc', ['doc' => $docs['type'] ?? null])
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-atoms.label class="mb-1.5" for="try-account-name">{{ __('webhooks.api_docs.account_name') }}</x-atoms.label>
                                        <x-atoms.input type="text" name="try-account-name" id="try-account-name" list="try-account-options" autocomplete="off" data-try-account />
                                        <datalist id="try-account-options" data-try-account-options></datalist>
                                        <p class="mt-1 font-body text-body-sm text-on-surface-variant">{{ __('webhooks.api_docs.account_hint') }}</p>
                                        @include('webhooks.partials.field-doc', ['doc' => $docs['account_name'] ?? null])
                                        <x-atoms.form-error for="try-account-name" />
                                    </div>
                                    <div>
                                        <x-atoms.label class="mb-1.5" for="try-amount">{{ __('webhooks.api_docs.amount') }}</x-atoms.label>
                                        <x-atoms.input type="text" inputmode="numeric" name="try-amount" id="try-amount" value="12000" data-money data-try-amount />
                                        @include('webhooks.partials.field-doc', ['doc' => $docs['amount'] ?? null])
                                        <x-atoms.form-error for="try-amount" />
                                    </div>
                                    <div>
                                        <x-atoms.label class="mb-1.5" for="try-note">{{ __('webhooks.api_docs.note') }}</x-atoms.label>
                                        <x-atoms.input type="text" name="try-note" id="try-note" value="Webhook test" data-try-note />
                                        @include('webhooks.partials.field-doc', ['doc' => $docs['note'] ?? null])
                                    </div>
                                    <div>
                                        <x-atoms.label class="mb-1.5" for="try-recorded-at">{{ __('webhooks.api_docs.recorded_at') }}</x-atoms.label>
                                        <x-atoms.input type="datetime-local" name="try-recorded-at" id="try-recorded-at" data-try-recorded-at />
                                        @include('webhooks.partials.field-doc', ['doc' => $docs['recorded_at'] ?? null])
                                        <x-atoms.form-error for="try-recorded-at" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-atoms.label class="mb-1.5" for="try-ip">IP <span class="font-normal text-on-surface-variant">({{ __('webhooks.api_docs.optional') }})</span></x-atoms.label>
                                        <x-atoms.input type="text" name="try-ip" id="try-ip" placeholder="203.0.113.5" data-try-ip />
                                        @include('webhooks.partials.field-doc', ['doc' => $docs['ip'] ?? null])
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1 font-body text-body-sm font-semibold text-on-surface">{{ __('webhooks.api_docs.body_preview') }}</p>
                                <pre class="overflow-x-auto rounded-md bg-surface-container p-3 font-mono text-body-sm text-on-surface" data-try-preview></pre>
                                <p class="mt-2 font-body text-body-sm text-on-surface-variant">{{ __('webhooks.api_docs.signature_help') }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-atoms.button type="button" data-try-execute>{{ __('webhooks.api_docs.execute') }}</x-atoms.button>
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
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    @vite('resources/js/webhooks/api-docs.js')
@endpush
