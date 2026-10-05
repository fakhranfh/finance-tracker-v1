import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { accountFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('account-form');
const itemId = form?.dataset.itemId;
const amountInput = form?.elements.namedItem('initial_balance');
const amountLabel = form?.querySelector('label[for="initial_balance"]');
const initialLabelText = amountLabel?.textContent;
const balanceLabelText = form?.dataset.balanceLabel;
const loaded = { initial_balance: '', balance: '' };

wireDevTools(form, accountFormFixture);

function adjustMode() {
    return form.elements.namedItem('adjust_by')?.value ?? 'initial_balance';
}

function syncAmountField() {
    const record = adjustMode() === 'record';

    amountLabel.textContent = record ? balanceLabelText : initialLabelText;
    if (record) {
        amountInput.removeAttribute('min');
    } else {
        amountInput.min = '0';
    }
    amountInput.value = record ? loaded.balance : loaded.initial_balance;
}

async function loadItem() {
    const { ok, data } = await get(routes.accounts.show(itemId));

    if (ok) {
        loaded.initial_balance = data.data.initial_balance ?? '';
        loaded.balance = data.data.balance ?? '';
        form.elements.namedItem('name').value = data.data.name ?? '';
        syncAmountField();
    }

    hideSkeleton('account-form', 'account-form-skeleton');
}

if (form) {
    if (itemId) {
        loadItem();
        form.elements.namedItem('adjust_by').forEach((radio) => radio.addEventListener('change', syncAmountField));
    }

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

        const amount = form.elements.namedItem('initial_balance').value;
        const payload = { name: form.elements.namedItem('name').value };

        if (itemId) {
            payload.adjust_by = adjustMode();
        }

        if (itemId && adjustMode() === 'record') {
            payload.balance = amount;
        } else {
            payload.initial_balance = amount;
        }

        try {
            const { ok, status, data } = await (itemId ? put(routes.accounts.update(itemId), payload) : post(routes.accounts.store, payload));

            if (ok) {
                sessionStorage.setItem('accounts-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.accounts;
                return;
            }

            if (status === 422 && data.errors) {
                if (data.errors.balance) {
                    data.errors.initial_balance = data.errors.balance;
                }
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'name');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'name');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
