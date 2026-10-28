<fieldset
    id="sub-goals"
    class="space-y-3"
    data-sub-goals-field
    data-totals-template="{{ __('sub-goals.form.totals', ['total' => ':total', 'target' => ':target']) }}"
    data-mismatch-message="{{ __('sub-goals.validation.target_total', ['total' => ':total', 'target' => ':target']) }}"
>
    <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('sub-goals.form.title') }}</legend>
    <p class="font-body text-body-sm text-on-surface-variant">{{ __('sub-goals.form.hint') }}</p>

    <div class="space-y-4" data-sub-goals-rows></div>

    <template>
        <div class="space-y-3 rounded-lg border border-outline-variant p-4" data-sub-goal-row>
            <input type="hidden" name="sub_goals.__INDEX__.id">
            <x-molecules.form-group name="sub_goals.__INDEX__.name" type="text" :label="__('sub-goals.fields.name')" maxlength="255" required />
            <div class="grid gap-3 sm:grid-cols-2">
                <x-molecules.form-group name="sub_goals.__INDEX__.target_amount" type="text" inputmode="numeric" :label="__('sub-goals.fields.target_amount')" data-money data-calculator required />
                <x-molecules.form-group name="sub_goals.__INDEX__.saved_amount" type="text" inputmode="numeric" :label="__('sub-goals.fields.saved_amount')" data-money data-calculator />
            </div>
            <x-molecules.form-group name="sub_goals.__INDEX__.desired_date" type="date" :label="__('sub-goals.fields.desired_date')" />
            <x-molecules.form-group name="sub_goals.__INDEX__.note" type="text" :label="__('sub-goals.fields.note')" maxlength="1000" />
            <div class="flex justify-end">
                <x-atoms.button type="button" variant="danger" data-sub-goal-remove>
                    {{ __('sub-goals.form.remove') }}
                </x-atoms.button>
            </div>
        </div>
    </template>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-atoms.button type="button" variant="secondary" data-sub-goal-add>
            {{ __('sub-goals.create') }}
        </x-atoms.button>
        <p class="hidden font-body text-body-sm" data-sub-goals-totals></p>
    </div>
    <x-atoms.form-error for="sub_goals" />
</fieldset>
