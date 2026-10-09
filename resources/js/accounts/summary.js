import { closeModal, openModal } from '../shared/modal.js';
import { hideSkeleton, setButtonLoading, showSkeleton } from '../shared/loading-state.js';
import { createTable } from '../shared/table/table.js';
import { del, get } from '../shared/http.js';
import { formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const MODAL_ID = 'account-summary-filter-modal';
const RESULTS_ID = 'account-summary-results';
const SKELETON_ID = 'account-summary-skeleton';
const SIGNS = { income: '+', expense: '-', transfer: '' };

const root = document.getElementById('account-summary');
const form = document.getElementById('account-summary-filter');
const inlineSlot = root.querySelector('[data-summary-filter-inline]');
const modalSlot = document.querySelector('[data-summary-filter-slot]');
const mobileQuery = window.matchMedia('(max-width: 639px)');

const accountId = root.dataset.accountId;
const recordsLabel = root.dataset.recordsLabel;
const typeLabels = JSON.parse(root.dataset.typeLabels);

// Period sent by the last summary response; the record list follows it.
let period = { date_from: null, date_to: null };

/**
 * Filter form lives inline on desktop and inside the filter modal on mobile,
 * the same way the table filters move in shared/table/table.js.
 */
function placeForm() {
    (mobileQuery.matches ? modalSlot : inlineSlot).append(form);
}

function setValue(key, text) {
    root.querySelector(`[data-summary-value="${key}"]`).textContent = text;
}

function render({ totals }) {
    setValue('income', formatMoney(totals.income));
    setValue('expense', formatMoney(totals.expense));
    setValue('transfer_in', formatMoney(totals.transfer_in));
    setValue('transfer_out', formatMoney(totals.transfer_out));
    setValue('net', formatMoney(totals.net));
    setValue('record_count', recordsLabel.replace(':count', totals.record_count));
}

async function loadAccountName() {
    const { ok, data } = await get(routes.accounts.show(accountId));
    const title = document.getElementById('account-name');

    title.textContent = ok ? data.data.name : title.dataset.notFound;
    document.title = ok ? `${data.data.name} - ${document.title}` : document.title;
    document.getElementById('account-name-skeleton').classList.add('hidden');
    title.classList.remove('hidden');
}

loadAccountName();

const recordsTable = createTable({
    ids: {
        table: 'account-records-table',
        wrapper: 'account-records-wrapper',
        toolbar: 'account-records-toolbar',
        generateButton: 'account-generate-records',
        generateCount: 'account-generate-count',
    },
    flashKey: 'account-records-flash',
    selectionKey: 'account-records-selection',
    defaultSort: { key: 'recorded_at', direction: 'desc' },
    params: () => ({ account_ids: [accountId], ...period }),
    // Generated records change the totals too, so refresh the summary.
    onDataChanged: () => load(),
    // Records are dated inside the period on screen, so they show up in its totals and list.
    generatePayload: () => ({
        date_from: period.date_from,
        date_to: period.date_to,
        type: document.getElementById('account-generate-type').value || null,
        amount: document.getElementById('account-generate-amount').value || null,
    }),
    routes: {
        index: routes.records.index,
        generate: routes.accounts.generateRecords(accountId),
    },
    row: (item) => ({
        cells: [
            formatDateTime(item.recorded_at),
            typeLabels[item.type] ?? item.type,
            item.type === 'transfer' ? '' : (item.category ?? root.dataset.uncategorized),
            item.to_account ? `${item.account} → ${item.to_account}` : item.account,
            `${SIGNS[item.type]}${formatMoney(item.amount)}`,
            item.note ?? '',
        ],
        editUrl: routes.pages.recordEdit(item.id),
        // Read-only list: rows are not selectable or deletable from this page.
        locked: true,
    }),
});

async function load() {
    const params = new URLSearchParams(new FormData(form));

    if (params.get('range') !== 'custom') {
        params.delete('from');
        params.delete('to');
    }

    window.history.replaceState(null, '', `?${params}`);

    showSkeleton(RESULTS_ID, SKELETON_ID);

    try {
        const { ok, data } = await get(`${routes.accounts.summary(accountId)}?${params}`);

        if (ok) {
            render(data.data);
            period = { date_from: data.data.range.from || null, date_to: data.data.range.to || null };
            await recordsTable.reload();
        }
    } finally {
        hideSkeleton(RESULTS_ID, SKELETON_ID);
    }
}

form.addEventListener('submit', (event) => {
    event.preventDefault();
    load();
});

// The date fields only apply to a custom range.
function syncDateFields() {
    const isCustom = form.elements.namedItem('range').value === 'custom';

    form.elements.namedItem('from').disabled = !isCustom;
    form.elements.namedItem('to').disabled = !isCustom;
}

// No apply button: any change applies at once. A custom range waits until both dates are set.
form.addEventListener('change', () => {
    syncDateFields();

    const range = form.elements.namedItem('range').value;
    const from = form.elements.namedItem('from').value;
    const to = form.elements.namedItem('to').value;

    if (range === 'custom' && (!from || !to)) {
        return;
    }

    if (mobileQuery.matches) {
        closeModal(MODAL_ID);
    }

    load();
});

// Dev-only: the button exists only in the local environment.
const deleteButton = document.getElementById('account-delete-records');

deleteButton?.addEventListener('click', async () => {
    if (!window.confirm(deleteButton.dataset.confirm)) {
        return;
    }

    setButtonLoading(deleteButton, true);

    const { ok } = await del(routes.accounts.deleteRecords(accountId));

    setButtonLoading(deleteButton, false);

    if (ok) {
        await load();
    }
});

document.querySelector(`[data-modal-open="${MODAL_ID}"]`).addEventListener('click', () => openModal(MODAL_ID));

placeForm();
mobileQuery.addEventListener('change', placeForm);
load();
