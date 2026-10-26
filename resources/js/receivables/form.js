import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { receivableFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('receivable-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, receivableFormFixture);

async function loadItem() {
    const { ok, data } = await get(routes.receivables.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('amount').value = data.data.amount ?? '';
        form.elements.namedItem('paid_amount').value = data.data.paid_amount ?? '';
        form.elements.namedItem('due_date').value = data.data.due_date ?? '';
        form.elements.namedItem('notes').value = data.data.notes ?? '';
        form.elements.namedItem('is_paid').checked = Boolean(data.data.is_paid);
    }

    hideSkeleton('receivable-form', 'receivable-form-skeleton');
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
            amount: form.elements.namedItem('amount').value,
            paid_amount: form.elements.namedItem('paid_amount').value,
            due_date: form.elements.namedItem('due_date').value,
            notes: form.elements.namedItem('notes').value,
            is_paid: form.elements.namedItem('is_paid').checked,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.receivables.update(itemId), payload) : post(routes.receivables.store, payload));

            if (ok) {
                sessionStorage.setItem('receivables-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.receivables;
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
