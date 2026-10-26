import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { routes } from '../routes.js';

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
            item.amount ?? '',
            item.paid_amount ?? '',
            item.due_date ? formatDateTime(item.due_date) : '',
            item.notes ?? '',
            item.is_paid ? '✓' : '—',
            formatDateTime(item.created_at),
        ],
        editUrl: routes.pages.debtEdit(item.id, item.type),
        locked: false,
    }),
});
