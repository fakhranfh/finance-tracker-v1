<div data-rotate-token>
    <div data-token-field>
        <x-molecules.copy-field name="token" :label="__('webhooks.fields.token')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
    </div>
    <x-molecules.token-field-skeleton class="hidden" data-token-skeleton />
    <x-atoms.button type="button" variant="secondary" class="mt-3" data-rotate-open>
        {{ __('webhooks.actions.rotate_token') }}
    </x-atoms.button>

    <x-organisms.modal id="rotate-webhook-token-modal" :title="__('webhooks.actions.confirm_rotate_token')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('webhooks.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" variant="danger" data-rotate-confirm>
                {{ __('webhooks.actions.rotate_token') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
</div>
