import { get } from '../shared/http.js';
import { hideSkeleton, showSkeleton } from '../shared/loading-state.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const RESULTS_ID = 'records-summary-results';
const SKELETON_ID = 'records-summary-skeleton';

/**
 * Totals of the records the table currently lists: call the returned function with the
 * table's search and filter query string whenever the table has loaded.
 */
export function createRecordsSummary() {
    const root = document.getElementById('records-summary');
    let latest = 0;

    const setValue = (key, text) => {
        root.querySelector(`[data-summary-value="${key}"]`).textContent = text;
    };

    return async (query) => {
        const request = ++latest;

        showSkeleton(RESULTS_ID, SKELETON_ID);

        const { ok, data } = await get(`${routes.records.summary}?${query}`);

        // A newer filter change has superseded this response.
        if (request !== latest) {
            return;
        }

        if (ok) {
            const totals = data.data;

            setValue('income', formatMoney(totals.income));
            setValue('expense', formatMoney(totals.expense));
            setValue('transfer_in', formatMoney(totals.transfer_in));
            setValue('transfer_out', formatMoney(totals.transfer_out));
            setValue('net', formatMoney(totals.net));
            setValue('record_count', root.dataset.recordsLabel.replace(':count', totals.record_count));
        }

        hideSkeleton(RESULTS_ID, SKELETON_ID);
    };
}
