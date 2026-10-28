import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { get } from '../shared/http.js';
import { routes } from '../routes.js';

const page = document.getElementById('debt-payments-page');
const debtId = page?.dataset.debtId;
const type = page?.dataset.type;
const subDebtId = new URLSearchParams(window.location.search).get('sub_debt_id') || '';
const summaryNode = document.querySelector('[data-payments-summary]');

/** The forms opened from here keep paying the same sub debt. */
function keepSubDebt(selector, base) {
    const link = document.querySelector(selector);

    if (link && subDebtId) {
        link.href = `${base}?sub_debt_id=${subDebtId}`;
    }
}

async function refreshSummary() {
    const { ok, data } = await get(`${routes.debts.payments(debtId)}?per_page=1`);

    if (ok) {
        summaryNode.textContent = page.dataset.summaryTemplate
            .replace(':paid', formatMoney(data.summary.paid))
            .replace(':amount', formatMoney(data.summary.amount))
            .replace(':remaining', formatMoney(data.summary.remaining));
        summaryNode.classList.remove('hidden');
    }
}

if (page) {
    keepSubDebt('#add-payment-link', routes.pages.debtPaymentCreate(debtId, type));
    keepSubDebt('#existing-payment-link', routes.pages.debtPaymentExisting(debtId, type));

    const { ok, data } = await get(routes.debts.show(debtId));

    if (ok) {
        document.querySelector('[data-debt-name]').textContent = data.data.name;
    }

    createTable({
        ids: {
            table: 'debt-payments-table',
            wrapper: 'debt-payments-table-wrapper',
            toolbar: 'debt-payments-table-toolbar',
            confirmDelete: 'confirm-delete-payment',
        },
        flashKey: 'debt-payments-flash-success',
        selectionKey: `debt-payments-table-selection-${debtId}`,
        deleteModalId: 'delete-payment-modal',
        deletedMessage: page.dataset.removedMessage,
        defaultSort: { key: 'recorded_at', direction: 'desc' },
        onLoaded: refreshSummary,
        params: () => ({ sub_debt_id: subDebtId }),
        routes: {
            index: routes.debts.payments(debtId),
            destroy: (id) => routes.debts.payment(debtId, id),
        },
        // A payment is usually a record: it can be opened on the records page, edited here and unlinked here.
        // One saved without a record has no account or category and nothing to open.
        row: (item) => ({
            cells: [formatDateTime(item.recorded_at), item.account ?? '-', item.category ?? '-', formatMoney(item.amount), item.note ?? '', item.sub_debt ?? '-'],
            viewUrl: item.record_id ? routes.pages.recordEdit(item.record_id) : null,
            editUrl: routes.pages.debtPaymentEdit(debtId, item.id, type),
        }),
    });
}
