import { createTable } from '../shared/table.js';
import { routes } from '../routes.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';

createTable({
    ids: {
        table: 'accounts-table',
        wrapper: 'accounts-table-wrapper',
        toolbar: 'accounts-table-toolbar',
        generateButton: 'generate-accounts',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-account',
        confirmBulkDelete: 'confirm-bulk-delete-accounts',
    },
    flashKey: 'accounts-flash-success',
    selectionKey: 'accounts-table-selection',
    deleteModalId: 'delete-account-modal',
    bulkDeleteModalId: 'bulk-delete-accounts-modal',
    routes: {
        index: routes.accounts.index,
        destroy: routes.accounts.destroy,
        bulkDestroy: routes.accounts.bulkDestroy,
        generate: routes.accounts.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            formatMoney(item.balance),
            formatDateTime(item.created_at),
        ],
        editUrl: routes.pages.accountEdit(item.id),
        locked: false,
    }),
});
