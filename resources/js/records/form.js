import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { toDateTimeLocal } from '../shared/datetime.js';
import '../shared/money-input.js';
import { searchableSelect } from '../shared/searchable-select.js';
import { wireDevTools } from './dev-tools.js';
import { recordFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('record-form');
const itemId = form?.dataset.itemId;
const categorySelect = form ? searchableSelect(form.elements.namedItem('category_id')) : null;

wireDevTools(form, recordFormFixture);
document.querySelector('[data-dev-autofill]')?.addEventListener('click', () => syncType());

function syncType() {
    const isTransfer = form.elements.type.value === 'transfer';
    const label = form.querySelector('[data-account-label]');

    form.querySelector('[data-transfer-only]').classList.toggle('hidden', !isTransfer);
    form.elements.namedItem('to_account_id').required = isTransfer;
    label.textContent = isTransfer ? label.dataset.transfer : label.dataset.default;

    // Categories share the record's type; transfers have none.
    const category = form.elements.namedItem('category_id');
    form.querySelector('[data-category-field]').classList.toggle('hidden', isTransfer);
    [...category.options].forEach((option) => {
        option.hidden = option.value !== '' && option.dataset.type !== form.elements.type.value;
    });
    if (isTransfer || category.selectedOptions[0]?.hidden) {
        category.value = '';
    }
    categorySelect.refresh();
}

/** Fills the account and category selects from the API; resolves to whether any account exists. */
async function loadOptions() {
    const [accounts, categories] = await Promise.all([
        getAll(`${routes.accounts.index}?sort=name`),
        getAll(`${routes.categories.index}?sort=name`),
    ]);

    ['account_id', 'to_account_id'].forEach((field) => {
        form.elements.namedItem(field).append(...(accounts ?? []).map((account) => new Option(account.name, account.id)));
    });

    form.elements.namedItem('category_id').append(
        ...(categories ?? []).map((category) => {
            const option = new Option(category.name, category.id);
            option.dataset.type = category.type;

            return option;
        }),
    );
    syncType();

    return (accounts ?? []).length > 0;
}

async function loadItem() {
    const { ok, data } = await get(routes.records.show(itemId));

    if (ok) {
        form.elements.type.value = data.data.type;
        form.elements.namedItem('account_id').value = data.data.account_id;
        form.elements.namedItem('to_account_id').value = data.data.to_account_id ?? '';
        syncType();
        form.elements.namedItem('category_id').value = data.data.category_id ?? '';
        form.elements.namedItem('amount').value = data.data.amount ?? '';
        form.elements.namedItem('note').value = data.data.note ?? '';
        form.elements.namedItem('recorded_at').value = toDateTimeLocal(data.data.recorded_at);
        syncType();
    } else {
        showGenericError(form, 'This record could not be found.', 'account_id');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

}

if (form) {
    form.elements.namedItem('recorded_at').value = toDateTimeLocal();
    form.querySelectorAll('input[name="type"]').forEach((radio) => radio.addEventListener('change', syncType));
    syncType();

    loadOptions().then(async (hasAccounts) => {
        if (!hasAccounts) {
            document.getElementById('record-form-skeleton')?.classList.add('hidden');
            document.getElementById('record-no-accounts')?.classList.remove('hidden');

            return;
        }

        if (itemId) {
            await loadItem();
        }

        hideSkeleton('record-form', 'record-form-skeleton');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFieldErrors(form);
        hideSnackbar();

        const clientErrors = validateForm(form);
        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(form, clientErrors);
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const payload = {
            type: form.elements.type.value,
            account_id: form.elements.namedItem('account_id').value,
            to_account_id: form.elements.type.value === 'transfer' ? form.elements.namedItem('to_account_id').value : null,
            category_id: form.elements.type.value === 'transfer' ? null : form.elements.namedItem('category_id').value || null,
            amount: form.elements.namedItem('amount').value,
            note: form.elements.namedItem('note').value,
            recorded_at: new Date(form.elements.namedItem('recorded_at').value).toISOString(),
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.records.update(itemId), payload) : post(routes.records.store, payload));

            if (ok) {
                sessionStorage.setItem('records-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.records;
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'account_id');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'account_id');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
