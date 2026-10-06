import { closeModal, openModal } from '../shared/modal.js';
import { hideSkeleton, showSkeleton } from '../shared/loading-state.js';
import { createTable } from '../shared/table/table.js';
import { get } from '../shared/http.js';
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

const recordsTable = createTable({
    ids: {
        table: 'account-records-table',
        wrapper: 'account-records-wrapper',
        toolbar: 'account-records-toolbar',
    },
    flashKey: 'account-records-flash',
    selectionKey: 'account-records-selection',
    defaultSort: { key: 'recorded_at', direction: 'desc' },
    params: () => ({ account_ids: [accountId], ...period }),
    routes: {
        index: routes.records.index,
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

    if (mobileQuery.matches) {
        closeModal(MODAL_ID);
    }

    load();
});

document.querySelector(`[data-modal-open="${MODAL_ID}"]`).addEventListener('click', () => openModal(MODAL_ID));

placeForm();
mobileQuery.addEventListener('change', placeForm);
load();
