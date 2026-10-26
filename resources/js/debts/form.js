import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, post, put } from '../shared/http.js';
import '../shared/money-input.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { debtFormFixture } from './dev-fixtures/form.js';
import { subDebtFixtures } from './dev-fixtures/sub-debts.js';
import { createSubDebtsField } from './sub-debts-field.js';

const form = document.getElementById('debt-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, () => debtFormFixture(form?.elements.namedItem('type')?.value));
const subDebtsField = form ? createSubDebtsField({ form }) : null;

// A new debt sometimes also gets sample sub debts that split the autofilled amount.
document.querySelector('[data-dev-autofill]')?.addEventListener('click', () => {
    if (!itemId) {
        subDebtsField.fill(Math.random() < 0.5 ? subDebtFixtures({ amount: form.elements.namedItem('amount').value }) : []);
    }
});

async function loadItem() {
    const { ok, data } = await get(routes.debts.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('amount').value = data.data.amount ?? '';
        form.elements.namedItem('paid_amount').value = data.data.paid_amount ?? '';
        form.elements.namedItem('due_date').value = data.data.due_date ?? '';
        form.elements.namedItem('notes').value = data.data.notes ?? '';
        form.elements.namedItem('is_paid').checked = Boolean(data.data.is_paid);
    }

    const subDebts = ok ? await getAll(routes.debts.subDebts(itemId)) : null;

    if (subDebts) {
        subDebtsField.load(subDebts);
    } else {
        subDebtsField.markUnloaded();
    }

    hideSkeleton('debt-form', 'debt-form-skeleton');
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
            type: form.elements.namedItem('type').value,
            name: form.elements.namedItem('name').value,
            amount: form.elements.namedItem('amount').value,
            paid_amount: form.elements.namedItem('paid_amount').value,
            due_date: form.elements.namedItem('due_date').value,
            notes: form.elements.namedItem('notes').value,
            is_paid: form.elements.namedItem('is_paid').checked,
            sub_debts: subDebtsField.payload(),
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.debts.update(itemId), payload) : post(routes.debts.store, payload));

            if (ok) {
                sessionStorage.setItem('debts-flash-success', form.dataset.successMessage);
                window.location.href = payload.type === 'receivable' ? routes.pages.receivables : routes.pages.debts;
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
