import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { routes } from '../routes.js';

createTable({
    ids: {
        table: 'receivables-table',
        wrapper: 'receivables-table-wrapper',
        toolbar: 'receivables-table-toolbar',
        generateButton: 'generate-receivables',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-receivable',
        confirmBulkDelete: 'confirm-bulk-delete-receivables',
    },
    flashKey: 'receivables-flash-success',
    selectionKey: 'receivables-table-selection',
    deleteModalId: 'delete-receivable-modal',
    bulkDeleteModalId: 'bulk-delete-receivables-modal',
    routes: {
        index: routes.receivables.index,
        destroy: routes.receivables.destroy,
        bulkDestroy: routes.receivables.bulkDestroy,
        generate: routes.receivables.generate,
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
        editUrl: routes.pages.receivableEdit(item.id),
        locked: false,
    }),
});
