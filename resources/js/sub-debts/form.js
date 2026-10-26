import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { createSubDebtsField } from '../debts/sub-debts-field.js';
import { subDebtFixtures } from '../debts/dev-fixtures/sub-debts.js';

const form = document.getElementById('sub-debt-form');
const debtId = form?.dataset.debtId;
const type = form?.dataset.type;

/**
 * Edits all sub debts of one debt on their own page. The debt's amount and due date sit in hidden inputs
 * so the repeater has something to keep in step; the server derives the debt's totals from the rows.
 */
async function loadDebt(subDebtsField) {
    const { ok, data } = await get(routes.debts.show(debtId));
    const subDebts = ok ? await getAll(routes.debts.subDebts(debtId)) : null;

    if (!ok || !subDebts) {
        showGenericError(form, 'This debt could not be found.', 'sub_debts');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    } else {
        document.querySelector('[data-debt-name]').textContent = data.data.name;
        form.elements.namedItem('amount').value = data.data.amount;
        form.elements.namedItem('due_date').value = data.data.due_date ?? '';
        subDebtsField.load(subDebts);
    }

    hideSkeleton('sub-debt-form', 'sub-debt-form-skeleton');
}

if (form) {
    const subDebtsField = createSubDebtsField({ form });

    loadDebt(subDebtsField);

    document.querySelector('[data-dev-autofill]')?.addEventListener('click', () => {
        subDebtsField.fill(subDebtFixtures({ amount: form.elements.namedItem('amount').value }));
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

        try {
            const { ok, status, data } = await put(routes.debts.subDebts(debtId), { sub_debts: subDebtsField.payload() });

            if (ok) {
                sessionStorage.setItem('sub-debts-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.debtSubDebts(debtId, type);
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'sub_debts');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'sub_debts');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
