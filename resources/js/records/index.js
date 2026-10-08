import { createMultiSelectFilter, createTable } from '../shared/table/table.js';
import { routes } from '../routes.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';

const typeLabels = JSON.parse(document.getElementById('records-table')?.dataset.typeLabels ?? '{}');
const SIGNS = { income: '+', expense: '-', transfer: '' };

const generateAccounts = document.getElementById('generate-accounts');

if (generateAccounts) {
    createMultiSelectFilter(generateAccounts);
}

const table = createTable({
    ids: {
        table: 'records-table',
        wrapper: 'records-table-wrapper',
        toolbar: 'records-table-toolbar',
        generateButton: 'generate-records',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-record',
        confirmBulkDelete: 'confirm-bulk-delete-records',
    },
    generatePayload: () => ({
        account_ids: [...document.querySelectorAll('#generate-accounts input[type="checkbox"]:checked')].map((checkbox) => Number(checkbox.value)),
    }),
    defaultSort: { key: 'recorded_at', direction: 'desc' },
    filtersId: 'records-filters',
    flashKey: 'records-flash-success',
    selectionKey: 'records-table-selection',
    deleteModalId: 'delete-record-modal',
    bulkDeleteModalId: 'bulk-delete-records-modal',
    routes: {
        index: routes.records.index,
        destroy: routes.records.destroy,
        bulkDestroy: routes.records.bulkDestroy,
        generate: routes.records.generate,
    },
    row: (item) => ({
        cells: [
            formatDateTime(item.recorded_at),
            typeLabels[item.type] ?? item.type,
            item.to_account ? `${item.account} → ${item.to_account}` : item.account,
            `${SIGNS[item.type]}${formatMoney(item.amount)}`,
            item.note ?? '',
        ],
        editUrl: routes.pages.recordEdit(item.id),
        locked: false,
    }),
});

document.querySelectorAll('[data-export-format]').forEach((button) => {
    button.addEventListener('click', () => {
        window.location.href = `${routes.records.export(button.dataset.exportFormat)}?${table.exportQuery()}`;
    });
});
