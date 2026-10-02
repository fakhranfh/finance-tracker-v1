import { createTable } from '../shared/table.js';
import { routes } from '../routes.js';

createTable({
    ids: {
        table: 'users-table',
        skeleton: 'users-table-skeleton',
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
    row: (user, { lockedId }) => ({
        cells: [user.name, user.email, new Date(user.created_at).toLocaleDateString()],
        editUrl: routes.pages.userEdit(user.id),
        locked: user.id === lockedId,
    }),
});
