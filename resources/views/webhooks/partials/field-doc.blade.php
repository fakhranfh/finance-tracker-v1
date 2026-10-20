@if ($doc && ($doc['required'] || $doc['description'] !== '' || ($doc['example'] ?? null) !== null))
    <div class="mt-1 space-y-0.5 font-body text-body-sm text-on-surface-variant">
        @if ($doc['required'])
            <p class="font-semibold text-error">{{ __('webhooks.api_docs.required') }}</p>
        @endif
        @if ($doc['description'] !== '')
            <p>{{ $doc['description'] }}</p>
        @endif
        @if (($doc['example'] ?? null) !== null)
            <p>{{ __('webhooks.api_docs.example') }}: <code class="font-mono">{{ $doc['example'] }}</code></p>
        @endif
    </div>
@endif
