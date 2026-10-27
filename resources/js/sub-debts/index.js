import { createTable } from '../shared/table/table.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { get, post } from '../shared/http.js';
import { closeModal, openModal } from '../shared/modal.js';
import { routes } from '../routes.js';

const page = document.getElementById('sub-debts-page');
const debtId = page?.dataset.debtId;
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

/** Whether the sub debt has been paid off, in words. */
function statusNode(item) {
    const text = document.createElement('span');

    text.textContent = item.is_paid ? page.dataset.paidLabel : page.dataset.unpaidLabel;

    return text;
}

let forgiveId = null;

function askForgive(id) {
    forgiveId = id;
    openModal('forgive-sub-debt-modal');
}

document.getElementById('confirm-forgive-sub-debt')?.addEventListener('click', async () => {
    const { ok, data } = await post(routes.debts.forgiveSubDebt(debtId, forgiveId));

    closeModal('forgive-sub-debt-modal');

    if (ok) {
        sessionStorage.setItem('sub-debts-flash-success', page.dataset.forgiveMessage);
        window.location.reload();
    } else {
        window.alert(data?.message ?? 'Something went wrong.');
    }
});

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
            return {
                cells: [
                    item.name ?? '',
                    formatMoney(item.amount),
                    formatMoney(item.paid_amount),
                    item.due_date ? formatDate(item.due_date) : '-',
                    statusNode(item),
                    item.note ?? '',
                ],
                viewUrl: `${routes.pages.debtPayments(debtId, data?.data?.type)}?sub_debt_id=${item.id}`,
                editUrl: routes.pages.debtSubDebtEdit(debtId, item.id, data?.data?.type),
                extraActions: item.is_paid ? [] : [{ label: page.dataset.forgiveLabel, onClick: () => askForgive(item.id) }],
                locked: false,
            };
        },
    });
}
