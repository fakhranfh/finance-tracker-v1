import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { routes } from '../routes.js';

createTable({
    ids: {
        table: 'users-table',
        wrapper: 'users-table-wrapper',
        toolbar: 'users-table-toolbar',
        generateButton: 'generate-users',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-user',
        confirmBulkDelete: 'confirm-bulk-delete-users',
    },
    flashKey: 'users-flash-success',
    selectionKey: 'users-table-selection',
    deleteModalId: 'delete-user-modal',
    bulkDeleteModalId: 'bulk-delete-users-modal',
    routes: {
        index: routes.users.index,
        destroy: routes.users.destroy,
        bulkDestroy: routes.users.bulkDestroy,
        generate: routes.users.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            item.email ?? '',
            item.role_label ?? '',
            formatDateTime(item.created_at),
        ],
        editUrl: routes.pages.userEdit(item.id),
        locked: false,
    }),
});
