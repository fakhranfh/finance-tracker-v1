<fieldset
    id="sub-debts"
    class="space-y-3"
    data-sub-debts-field
    data-totals-template="{{ __('sub-debts.form.totals', ['amount' => ':amount', 'paid' => ':paid']) }}"
>
    <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('sub-debts.form.title') }}</legend>
    <p class="font-body text-body-sm text-on-surface-variant">{{ __('sub-debts.form.hint') }}</p>

    <div class="space-y-4" data-sub-debts-rows></div>

    <template>
        <div class="space-y-3 rounded-lg border border-outline-variant p-4" data-sub-debt-row>
            <input type="hidden" name="sub_debts.__INDEX__.id">
            <x-molecules.form-group name="sub_debts.__INDEX__.name" type="text" :label="__('sub-debts.fields.name')" maxlength="255" required />
            <div class="grid gap-3 sm:grid-cols-2">
                <x-molecules.form-group name="sub_debts.__INDEX__.amount" type="text" inputmode="numeric" :label="__('sub-debts.fields.amount')" data-money required />
                <x-molecules.form-group name="sub_debts.__INDEX__.paid_amount" type="text" inputmode="numeric" :label="__('sub-debts.fields.paid_amount')" data-money />
            </div>
            <x-molecules.form-group name="sub_debts.__INDEX__.due_date" type="date" :label="__('sub-debts.fields.due_date')" />
            <div class="flex items-center gap-2">
                <x-atoms.checkbox name="sub_debts.__INDEX__.same_due_date" id="sub_debts.__INDEX__.same_due_date" />
                <x-atoms.label for="sub_debts.__INDEX__.same_due_date">{{ __('sub-debts.fields.same_due_date') }}</x-atoms.label>
            </div>
            <x-molecules.form-group name="sub_debts.__INDEX__.note" type="text" :label="__('sub-debts.fields.note')" maxlength="1000" />
            <div class="flex justify-end">
                <x-atoms.button type="button" variant="danger" data-sub-debt-remove>
                    {{ __('sub-debts.form.remove') }}
                </x-atoms.button>
            </div>
        </div>
    </template>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-atoms.button type="button" variant="secondary" data-sub-debt-add>
            {{ __('sub-debts.create') }}
        </x-atoms.button>
        <p class="hidden font-body text-body-sm text-on-surface-variant" data-sub-debts-totals></p>
    </div>
    <x-atoms.form-error for="sub_debts" />
</fieldset>
