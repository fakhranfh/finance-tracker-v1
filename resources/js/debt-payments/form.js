import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { get, getAll, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { toDateTimeLocal } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { closeModal, openModal } from '../shared/modal.js';
import '../shared/money-input.js';
import { searchableSelect } from '../shared/searchable-select.js';
import { wireDevTools } from '../debts/dev-tools.js';
import { debtPaymentFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('debt-payment-form');
const debtId = form?.dataset.debtId;
const type = form?.dataset.type;
// Editing: the payment as listed, a record id or the negated id of a payment saved without a record (which has no account or category).
const paymentKey = form?.dataset.paymentId ? Number(form.dataset.paymentId) : null;
const hasRecordFields = Boolean(form?.elements.namedItem('account_id'));
const subDebtSelect = form?.elements.namedItem('sub_debt_id');
const amountInput = form?.elements.namedItem('amount');
const searchables = form
    ? ['sub_debt_id', 'account_id', 'category_id']
          .filter((name) => form.elements.namedItem(name))
          .map((name) => searchableSelect(form.elements.namedItem(name)))
    : [];
const refreshSearchables = () => searchables.forEach((searchable) => searchable.refresh());
const subDebts = [];
let debtRemaining = 0;

// A debt is repaid with expense records, a receivable settled with income records.
const recordType = type === 'receivable' ? 'income' : 'expense';

wireDevTools(form, () => debtPaymentFormFixture(remainingOfSelection()));

/** What is still owed on the chosen sub debt, or on the debt itself when it has none. */
function remainingOfSelection() {
    const subDebt = subDebts.find((item) => String(item.id) === subDebtSelect.value);

    return subDebt ? subDebt.remaining_amount : debtRemaining;
}

function fillOptions(select, items, label) {
    items.forEach((item) => {
        const option = document.createElement('option');

        option.value = item.id;
        option.textContent = label(item);
        select.append(option);
    });
}

async function load() {
    const [debt, accounts, categories, subs, payment] = await Promise.all([
        get(routes.debts.show(debtId)),
        hasRecordFields ? getAll(`${routes.accounts.index}?sort=name`) : [],
        hasRecordFields ? getAll(`${routes.categories.index}?sort=name`) : [],
        getAll(routes.debts.subDebts(debtId)),
        paymentKey ? get(routes.debts.payment(debtId, paymentKey)) : null,
    ]);

    if (debt.ok) {
        document.querySelector('[data-debt-name]').textContent = debt.data.data.name;
        debtRemaining = debt.data.data.remaining_amount;
    }

    if (hasRecordFields) {
        fillOptions(form.elements.namedItem('account_id'), accounts ?? [], (account) => account.name);
        fillOptions(
            form.elements.namedItem('category_id'),
            (categories ?? []).filter((category) => category.type === recordType),
            (category) => category.name,
        );
    }

    // The sub debt a payment already pays stays choosable even when it has nothing left to pay.
    const current = payment?.ok ? payment.data.data : null;

    subDebts.push(...(subs ?? []).filter((subDebt) => subDebt.remaining_amount > 0 || subDebt.id === current?.sub_debt_id));

    if ((subs ?? []).length > 0) {
        form.querySelector('[data-sub-debt-group]').classList.remove('hidden');
        fillOptions(subDebtSelect, subDebts, (subDebt) => `${subDebt.name} (${formatMoney(subDebt.remaining_amount)})`);
        subDebtSelect.value = current ? (current.sub_debt_id ?? '') : (new URLSearchParams(window.location.search).get('sub_debt_id') ?? '');
    }

    if (current) {
        amountInput.value = String(current.amount);
        form.elements.namedItem('recorded_at').value = toDateTimeLocal(current.recorded_at);
        form.elements.namedItem('note').value = current.note ?? '';

        if (hasRecordFields) {
            form.elements.namedItem('account_id').value = current.account_id ?? '';
            form.elements.namedItem('category_id').value = current.category_id ?? '';
        }
    } else {
        amountInput.value = String(remainingOfSelection());
    }

    refreshSearchables();

    if (paymentKey) {
        hideSkeleton('debt-payment-form', 'debt-payment-form-skeleton');
    }
}

if (form) {
    form.elements.namedItem('recorded_at').value = toDateTimeLocal();

    // Registered after wireDevTools, so it runs once autofill has set the values.
    document.querySelector('[data-dev-autofill]')?.addEventListener('click', refreshSearchables);

    load();

    // A new payment suggests paying what is left; an edited one keeps its amount.
    if (!paymentKey) {
        subDebtSelect.addEventListener('change', () => {
            amountInput.value = String(remainingOfSelection());
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        clearFieldErrors(form);
        hideSnackbar();

        const clientErrors = validateForm(form);
        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(form, clientErrors);
            return;
        }

        if (paymentKey) {
            save();
            return;
        }

        // Ask first whether the payment should be saved as a record on the account too.
        openModal('debt-payment-record-modal');
    });

    document.querySelectorAll('[data-record-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal('debt-payment-record-modal');
            save(button.dataset.recordChoice === '1');
        });
    });

    async function save(createRecord) {
        const submitButton = form.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const payload = {
            sub_debt_id: subDebtSelect.value || null,
            amount: amountInput.value,
            note: form.elements.namedItem('note').value || null,
            recorded_at: new Date(form.elements.namedItem('recorded_at').value).toISOString(),
        };

        if (hasRecordFields) {
            payload.account_id = form.elements.namedItem('account_id').value;
            payload.category_id = form.elements.namedItem('category_id').value || null;
        }

        if (!paymentKey) {
            payload.create_record = createRecord;
        }

        try {
            const { ok, status, data } = await (paymentKey
                ? put(routes.debts.payment(debtId, paymentKey), payload)
                : post(routes.debts.payments(debtId), payload));

            if (ok) {
                sessionStorage.setItem('debt-payments-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.debtPayments(debtId, type);
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'amount');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'amount');
        } finally {
            setButtonLoading(submitButton, false);
        }
    }
}
