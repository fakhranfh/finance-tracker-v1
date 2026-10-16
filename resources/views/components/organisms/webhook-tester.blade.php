@props(['id'])

<section id="webhook-tester" data-webhook-id="{{ $id }}" class="hidden w-full space-y-3 rounded-lg border border-outline-variant bg-surface p-6">
    <div>
        <h2 class="font-heading text-body-lg font-semibold text-on-surface">{{ __('webhooks.test.title') }}</h2>
        <p class="mt-1 font-body text-body-sm text-on-surface-variant">{{ __('webhooks.test.description') }}</p>
    </div>

    <x-atoms.label for="webhook-test-payload">{{ __('webhooks.test.payload') }}</x-atoms.label>
    <x-atoms.textarea name="webhook-test-payload" id="webhook-test-payload" rows="9" spellcheck="false" class="font-mono !text-body-sm" />

    <x-atoms.button type="button" id="webhook-test-send" variant="secondary">
        {{ __('webhooks.test.send') }}
    </x-atoms.button>

    <pre id="webhook-test-result" class="hidden overflow-x-auto rounded-md bg-surface-container p-3 font-mono text-body-sm text-on-surface"></pre>
</section>
