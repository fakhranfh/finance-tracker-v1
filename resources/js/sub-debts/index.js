import { createTable } from '../shared/table/table.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { get, put } from '../shared/http.js';
import { routes } from '../routes.js';

const page = document.getElementById('sub-debts-page');
const debtId = page?.dataset.debtId;
const statusError = document.querySelector('[data-status-error]');
const summaryNode = document.querySelector('[data-sub-debts-summary]');

async function refreshSummary() {
    const { ok, data } = await get(`${routes.debts.subDebts(debtId)}?per_page=1`);

    if (ok) {
        summaryNode.textContent = page.dataset.summaryTemplate
            .replace(':paid_amount', formatMoney(data.summary.paid_total))
            .replace(':paid', data.summary.paid)
            .replace(':total', data.summary.total)
            .replace(':amount', formatMoney(data.summary.amount_total));
        summaryNode.classList.remove('hidden');
    }
}

/** Inline checkbox: saves the sub debt as paid / unpaid and reverts the tick if that fails. */
function paidNode(item, paidAmountNode) {
    const label = document.createElement('label');
    label.className = 'inline-flex cursor-pointer items-center gap-2';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-default';
    checkbox.checked = item.is_paid;
    checkbox.setAttribute('aria-label', item.name);

    const text = document.createElement('span');
    text.textContent = item.is_paid ? page.dataset.paidLabel : page.dataset.unpaidLabel;

    checkbox.addEventListener('change', async () => {
        checkbox.disabled = true;
        statusError.classList.add('hidden');

        try {
            const { ok, data } = await put(routes.debts.subDebt(debtId, item.id), { is_paid: checkbox.checked });

            if (ok) {
                item.is_paid = data.data.is_paid;
                item.paid_amount = data.data.paid_amount;
                text.textContent = item.is_paid ? page.dataset.paidLabel : page.dataset.unpaidLabel;
                paidAmountNode.textContent = formatMoney(item.paid_amount);
                refreshSummary();
            } else {
                checkbox.checked = item.is_paid;
                statusError.classList.remove('hidden');
            }
        } catch {
            checkbox.checked = item.is_paid;
            statusError.classList.remove('hidden');
        } finally {
            checkbox.disabled = false;
        }
    });

    label.append(checkbox, text);

    return label;
}

if (page) {
    const { ok, data } = await get(routes.debts.show(debtId));

    if (ok) {
        document.querySelector('[data-debt-name]').textContent = data.data.name;
    }

    createTable({
        ids: {
            table: 'sub-debts-table',
            wrapper: 'sub-debts-table-wrapper',
            toolbar: 'sub-debts-table-toolbar',
            generateButton: 'generate-sub-debts',
            generateCount: 'generate-count',
            confirmDelete: 'confirm-delete-sub-debt',
            confirmBulkDelete: 'confirm-bulk-delete-sub-debts',
        },
        flashKey: 'sub-debts-flash-success',
        selectionKey: `sub-debts-table-selection-${debtId}`,
        deleteModalId: 'delete-sub-debt-modal',
        bulkDeleteModalId: 'bulk-delete-sub-debts-modal',
        defaultSort: { key: 'created_at', direction: 'asc' },
        onLoaded: refreshSummary,
        routes: {
            index: routes.debts.subDebts(debtId),
            destroy: (id) => routes.debts.subDebt(debtId, id),
            bulkDestroy: routes.debts.subDebts(debtId),
            generate: routes.debts.generateSubDebts(debtId),
        },
        row: (item) => {
            const paidAmountNode = document.createElement('span');

            paidAmountNode.textContent = formatMoney(item.paid_amount);

            return {
                cells: [
                    item.name ?? '',
                    formatMoney(item.amount),
                    paidAmountNode,
                    item.due_date ? formatDate(item.due_date) : '-',
                    paidNode(item, paidAmountNode),
                    item.note ?? '',
                ],
                editUrl: routes.pages.debtSubDebtEdit(debtId, item.id, data?.data?.type),
                locked: false,
            };
        },
    });
}
