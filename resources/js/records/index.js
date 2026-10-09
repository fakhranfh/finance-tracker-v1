import { createMultiSelectFilter, createTable, fillMultiSelect } from '../shared/table/table.js';
import { getAll } from '../shared/http.js';
import { routes } from '../routes.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';

const typeLabels = JSON.parse(document.getElementById('records-table')?.dataset.typeLabels ?? '{}');
const uncategorized = document.getElementById('records-table')?.dataset.uncategorized ?? '';
const SIGNS = { income: '+', expense: '-', transfer: '' };

/** Account and category options come from the API, so the dropdowns are filled before the table wires its filters. */
async function loadOptions() {
    const [accounts, categories] = await Promise.all([
        getAll(`${routes.accounts.index}?sort=name`),
        getAll(`${routes.categories.index}?sort=name`),
    ]);
    const asOptions = (items) => (items ?? []).map((item) => ({ value: item.id, label: item.name }));
    const accountOptions = asOptions(accounts);

    fillMultiSelect(document.getElementById('filter-account_ids'), accountOptions);
    fillMultiSelect(document.getElementById('filter-category_ids'), [{ value: 'none', label: uncategorized }, ...asOptions(categories)]);

    const generateAccounts = document.getElementById('generate-accounts');

    if (generateAccounts) {
        fillMultiSelect(generateAccounts, accountOptions, { checked: true });
        createMultiSelectFilter(generateAccounts);
        document.getElementById('generate-accounts-wrapper').classList.toggle('hidden', accountOptions.length === 0);
    }

    const noAccounts = document.getElementById('records-no-accounts');
    noAccounts.classList.toggle('hidden', accountOptions.length > 0);
    noAccounts.classList.toggle('flex', accountOptions.length === 0);
    document.getElementById('add-record').hidden = accountOptions.length === 0;
}

await loadOptions();

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
            item.type === 'transfer' ? '' : (item.category ?? uncategorized),
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
