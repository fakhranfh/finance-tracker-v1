<details class="mb-6 rounded-lg border border-outline-variant bg-surface p-4" id="webhook-guide">
    <summary class="cursor-pointer font-heading text-body-md font-semibold text-on-surface">
        {{ __('webhooks.guide.title') }}
    </summary>

    <div class="mt-4 space-y-4 font-body text-body-sm text-on-surface-variant">
        <ol class="list-decimal space-y-2 pl-5">
            <li>{{ __('webhooks.guide.step_create') }}</li>
            <li>{{ __('webhooks.guide.step_copy') }}</li>
            <li>{{ __('webhooks.guide.step_send') }}</li>
            <li>{{ __('webhooks.guide.step_test') }}</li>
            <li>{{ __('webhooks.guide.step_ips') }}</li>
            <li>{{ __('webhooks.guide.step_response') }}</li>
        </ol>

        <div>
            <p class="mb-2 font-semibold text-on-surface">{{ __('webhooks.guide.example') }}</p>
            <pre class="overflow-x-auto rounded-md bg-surface-container p-3 text-on-surface"><code>curl -X POST {{ url('/api/webhooks/records') }} \
  -H "X-Webhook-Token: TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "expense",
    "account_name": "Cash",
    "category_id": 2,
    "amount": "12.50",
    "note": "Coffee",
    "recorded_at": "2026-01-15T10:30:00+07:00"
  }'</code></pre>
        </div>

        <ul class="list-disc space-y-1 pl-5">
            <li>{{ __('webhooks.guide.field_type') }}</li>
            <li>{{ __('webhooks.guide.field_account') }}</li>
            <li>{{ __('webhooks.guide.field_category') }}</li>
            <li>{{ __('webhooks.guide.field_amount') }}</li>
            <li>{{ __('webhooks.guide.field_recorded_at') }}</li>
        </ul>

        <p>{{ __('webhooks.guide.errors') }}</p>
    </div>
</details>
