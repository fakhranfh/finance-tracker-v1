import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { accountFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('account-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, accountFormFixture);

async function loadItem() {
    const { ok, data } = await get(routes.accounts.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('balance').value = data.data.balance ?? '';
    }

    hideSkeleton('account-form', 'account-form-skeleton');
}

if (form) {
    if (itemId) {
        loadItem();
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

        const payload = {
            name: form.elements.namedItem('name').value,
            balance: form.elements.namedItem('balance').value,
        };

        if (itemId) {
            payload.adjust_by = form.elements.namedItem('adjust_by').value;
        }

        try {
            const { ok, status, data } = await (itemId ? put(routes.accounts.update(itemId), payload) : post(routes.accounts.store, payload));

            if (ok) {
                sessionStorage.setItem('accounts-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.accounts;
                return;
            }

            if (status === 422 && data.errors) {
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
