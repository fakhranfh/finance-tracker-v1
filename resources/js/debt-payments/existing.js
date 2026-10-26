import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { get, getAll, post } from '../shared/http.js';
import { routes } from '../routes.js';

const page = document.getElementById('debt-existing-page');
const debtId = page?.dataset.debtId;
const type = page?.dataset.type;
const subDebtSelect = document.getElementById('sub_debt_id');
const errorNode = document.querySelector('[data-use-error]');

/** A debt split into sub debts is paid per sub debt, so the group with their select only shows then. */
async function loadSubDebts() {
    const subDebts = (await getAll(routes.debts.subDebts(debtId))) ?? [];
    const preselected = new URLSearchParams(window.location.search).get('sub_debt_id');

    subDebts
        .filter((subDebt) => subDebt.remaining_amount > 0)
        .forEach((subDebt) => {
            const option = document.createElement('option');

            option.value = subDebt.id;
            option.textContent = `${subDebt.name} (${formatMoney(subDebt.remaining_amount)})`;
            subDebtSelect.append(option);
        });

    if (subDebts.length > 0) {
        document.querySelector('[data-sub-debt-group]').classList.remove('hidden');
        subDebtSelect.value = preselected ?? '';
    }
}

/** Pays the debt off with the chosen record, then goes back to the payments. */
async function useRecord(item) {
    errorNode.classList.add('hidden');

    const { ok, data } = await post(routes.debts.payments(debtId), {
        record_id: item.id,
        sub_debt_id: subDebtSelect.value || null,
    });

    if (ok) {
        sessionStorage.setItem('debt-payments-flash-success', page.dataset.usedMessage);
        window.location.href = routes.pages.debtPayments(debtId, type);

        return;
    }

    const first = Object.values(data.errors ?? {})[0]?.[0];

    errorNode.textContent = first ?? data.message ?? 'Something went wrong.';
    errorNode.classList.remove('hidden');
}

if (page) {
    const { ok, data } = await get(routes.debts.show(debtId));

    if (ok) {
        document.querySelector('[data-debt-name]').textContent = data.data.name;
    }

    await loadSubDebts();

    createTable({
        ids: {
            table: 'debt-existing-table',
            wrapper: 'debt-existing-table-wrapper',
            toolbar: 'debt-existing-table-toolbar',
        },
        flashKey: 'debt-existing-flash-success',
        selectionKey: `debt-existing-table-selection-${debtId}`,
        defaultSort: { key: 'recorded_at', direction: 'desc' },
        routes: {
            index: routes.debts.paymentsAvailable(debtId),
        },
        // The row's action button picks the record instead of editing it.
        row: (item) => ({
            cells: [formatDateTime(item.recorded_at), item.account, item.category ?? '-', formatMoney(item.amount), item.note ?? ''],
            onEdit: () => useRecord(item),
            locked: true,
        }),
    });
}
