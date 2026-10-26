import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import '../shared/money-input.js';
import { wireDevTools } from '../debts/dev-tools.js';
import { subDebtItemFormFixture } from './dev-fixtures/item-form.js';

const form = document.getElementById('sub-debt-item-form');
const debtId = form?.dataset.debtId;
const type = form?.dataset.type;
const itemId = form?.dataset.itemId;

wireDevTools(form, subDebtItemFormFixture);

async function loadItem() {
    const [debt, item] = await Promise.all([get(routes.debts.show(debtId)), get(routes.debts.subDebt(debtId, itemId))]);

    if (debt.ok) {
        document.querySelector('[data-debt-name]').textContent = debt.data.data.name;
    }

    if (item.ok) {
        ['name', 'amount', 'due_date', 'note'].forEach((field) => {
            form.elements.namedItem(field).value = item.data.data[field] ?? '';
        });
    } else {
        showGenericError(form, 'This sub debt could not be found.', 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    hideSkeleton('sub-debt-item-form', 'sub-debt-item-form-skeleton');
}

async function loadDebtName() {
    const { ok, data } = await get(routes.debts.show(debtId));

    if (ok) {
        document.querySelector('[data-debt-name]').textContent = data.data.name;
    }
}

if (form) {
    if (itemId) {
        loadItem();
    } else {
        loadDebtName();
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
            due_date: form.elements.namedItem('due_date').value || null,
            note: form.elements.namedItem('note').value || null,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.debts.subDebt(debtId, itemId), payload) : post(routes.debts.subDebts(debtId), payload));

            if (ok) {
                sessionStorage.setItem('sub-debts-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.debtSubDebts(debtId, type);
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
