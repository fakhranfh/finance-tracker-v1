<x-organisms.modal id="cycle-entry-modal" :title="__('cycles.form.entry_add')">
    <form id="cycle-entry-form" novalidate class="space-y-5" data-add-title="{{ __('cycles.form.entry_add') }}" data-edit-title="{{ __('cycles.form.entry_edit') }}">
        <x-molecules.form-group name="name" type="text" :label="__('cycles.form.product')" :placeholder="__('cycles.form.product_placeholder')" maxlength="80" required />
        <x-molecules.form-group name="price" type="text" inputmode="numeric" :label="__('cycles.form.price')" data-money required />
        <x-molecules.form-group name="bought_at" type="date" :label="__('cycles.form.bought_at')" required />

        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('cycles.form.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="submit">
                {{ __('cycles.form.save') }}
            </x-atoms.button>
        </div>
    </form>
</x-organisms.modal>

<x-organisms.modal id="cycle-finish-modal" :title="__('cycles.form.finish_title')">
    <form id="cycle-finish-form" novalidate class="space-y-5">
        <x-molecules.form-group name="finished_at" type="date" :label="__('cycles.form.finished_at')" required />

        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('cycles.form.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="submit">
                {{ __('cycles.form.save') }}
            </x-atoms.button>
        </div>
    </form>
</x-organisms.modal>

<x-organisms.modal id="cycle-bulk-delete-modal" :title="__('cycles.form.bulk_delete_title')">
    <p class="mb-6 font-body text-body-md text-on-surface-variant">{{ __('cycles.form.bulk_delete_text') }}</p>
    <div class="flex justify-end gap-3">
        <x-atoms.button type="button" variant="secondary" data-modal-close>
            {{ __('cycles.form.cancel') }}
        </x-atoms.button>
        <x-atoms.button type="button" id="confirm-bulk-delete-cycle" variant="danger">
            {{ __('cycles.form.delete') }}
        </x-atoms.button>
    </div>
</x-organisms.modal>

<x-organisms.modal id="cycle-delete-modal" :title="__('cycles.form.delete_title')">
    <p class="mb-6 font-body text-body-md text-on-surface-variant">{{ __('cycles.form.delete_text') }}</p>
    <div class="flex justify-end gap-3">
        <x-atoms.button type="button" variant="secondary" data-modal-close>
            {{ __('cycles.form.cancel') }}
        </x-atoms.button>
        <x-atoms.button type="button" id="confirm-delete-cycle" variant="danger">
            {{ __('cycles.form.delete') }}
        </x-atoms.button>
    </div>
</x-organisms.modal>
