import { createTable } from '../shared/table.js';
import { routes } from '../routes.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';

const typeLabels = JSON.parse(document.getElementById('records-table')?.dataset.typeLabels ?? '{}');
const SIGNS = { income: '+', expense: '-', transfer: '' };

const accountCheckboxes = [...document.querySelectorAll('[data-generate-account]')];
const accountsSummary = document.querySelector('[data-accounts-summary]');

function refreshAccountsSummary() {
    const selected = accountCheckboxes.filter((checkbox) => checkbox.checked).length;
    accountsSummary.textContent = accountsSummary.dataset.summaryTemplate.replace(':count', selected);
}

accountCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshAccountsSummary));

createTable({
    ids: {
        table: 'records-table',
        skeleton: 'records-table-skeleton',
        wrapper: 'records-table-wrapper',
        toolbar: 'records-table-toolbar',
        generateButton: 'generate-records',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-record',
        confirmBulkDelete: 'confirm-bulk-delete-records',
    },
    generatePayload: () => ({
        account_ids: [...document.querySelectorAll('[data-generate-account]:checked')].map((checkbox) => Number(checkbox.value)),
    }),
    defaultSort: { key: 'recorded_at', direction: 'desc' },
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
