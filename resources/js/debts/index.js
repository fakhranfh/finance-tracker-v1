import { createTable } from '../shared/table/table.js';
import { formatDate, formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';
import { post } from '../shared/http.js';
import { closeModal, openModal } from '../shared/modal.js';

let forgiveId = null;

function askForgive(id) {
    forgiveId = id;
    openModal('forgive-debt-modal');
}

document.getElementById('confirm-forgive-debt')?.addEventListener('click', async () => {
    const { ok, data } = await post(routes.debts.forgive(forgiveId));

    closeModal('forgive-debt-modal');

    if (ok) {
        sessionStorage.setItem('debts-flash-success', document.getElementById('debts-filters').dataset.forgiveMessage);
        window.location.reload();
    } else {
        window.alert(data?.message ?? 'Something went wrong.');
    }
});

createTable({
    ids: {
        table: 'debts-table',
        wrapper: 'debts-table-wrapper',
        toolbar: 'debts-table-toolbar',
        generateButton: 'generate-debts',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-debt',
        confirmBulkDelete: 'confirm-bulk-delete-debts',
    },
    filtersId: 'debts-filters',
    generatePayload: () => ({ type: document.getElementById('debts-filters')?.dataset.debtType }),
    flashKey: 'debts-flash-success',
    selectionKey: 'debts-table-selection',
    deleteModalId: 'delete-debt-modal',
    bulkDeleteModalId: 'bulk-delete-debts-modal',
    routes: {
        index: routes.debts.index,
        destroy: routes.debts.destroy,
        bulkDestroy: routes.debts.bulkDestroy,
        generate: routes.debts.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            formatMoney(item.amount),
            formatMoney(item.paid_amount),
            item.due_date ? formatDate(item.due_date) : '',
            item.notes ?? '',
            item.is_paid ? '✓' : '—',
            formatDateTime(item.created_at),
        ],
        viewUrl: routes.pages.debtSubDebts(item.id, item.type),
        extraActions: [
            { label: document.getElementById('debts-filters').dataset.paymentsLabel, url: routes.pages.debtPayments(item.id, item.type) },
            ...(item.is_paid ? [] : [{ label: document.getElementById('debts-filters').dataset.forgiveLabel, onClick: () => askForgive(item.id) }]),
        ],
        editUrl: routes.pages.debtEdit(item.id, item.type),
        locked: false,
    }),
});
