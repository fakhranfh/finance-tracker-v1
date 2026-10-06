import { createTable } from '../shared/table/table.js';
import { routes } from '../routes.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { createAccountOrder } from './reorder.js';

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
    defaultSort: { key: 'name', direction: 'asc' },
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

createAccountOrder({
    openButton: document.getElementById('open-reorder-accounts'),
    modalId: 'reorder-accounts-modal',
    list: document.getElementById('reorder-accounts-list'),
    saveButton: document.getElementById('save-reorder-accounts'),
    routes: {
        index: routes.accounts.index,
        reorder: routes.accounts.reorder,
    },
});
